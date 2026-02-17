# Fintech Lab - Pipeline de Transacoes com Microsservicos

Projeto de estudo que simula um pipeline simplificado de processamento de transacoes com cartao, utilizando microsservicos, mensageria assincrona, event sourcing e orquestracao com Kubernetes.

## Tecnologias Utilizadas
- **PHP 8.3** com **Hyperf 3.1** (Swoole)
- **RabbitMQ 3** (mensageria AMQP)
- **MySQL 8** (persistencia)
- **Redis 7** (anti-fraude)
- **Docker** & **Docker Compose**
- **Kubernetes** (Minikube)

## Arquitetura

```
                         POST /transacao                POST /estorno
                              |                              |
                              v                              v
                        +-----------+               +-----------+
                        |  Gateway  |               |  Gateway  |
                        +-----------+               +-----------+
                              |                          |
                    transacao.recebida          estorno.solicitado
                              |                          |
                              v                          v
                      +-------------+          +-------+
                      | Autorizador |--------->| Redis |  Anti-fraude (frequencia)
                      +-------------+          +-------+
                        |         |              |         |
              transacao.aprovada  negada  estorno.aprovado  negado
                        |                        |
                        v                        v
                    +--------+
                    | Ledger |  Event sourcing + projecao de saldo
                    +--------+
                        |
                        v
                    +-------+
                    | MySQL |
                    +-------+
```

Tres microsservicos Hyperf comunicando-se exclusivamente via **RabbitMQ** (exchange `transacoes`, tipo topic):

1. **Gateway** — Recebe requisicoes HTTP, valida dados do cartao e publica `TransacaoRecebida` ou `EstornoSolicitado`
2. **Autorizador** — Consome `TransacaoRecebida` e `EstornoSolicitado`, aplica regras anti-fraude, verifica saldo, debita/credita conta e publica resultado
3. **Ledger** — Consome `TransacaoAprovada` e `EstornoAprovado`, registra eventos contabeis imutaveis e atualiza projecao de saldo

### Conceitos-Chave

- **Event Sourcing**: O Ledger armazena todas as transacoes como eventos imutaveis. O saldo e uma projecao recalculavel a partir dos eventos
- **Consistencia Eventual**: Servicos comunicam-se de forma assincrona via RabbitMQ. Nao ha chamadas sincronas entre servicos
- **Idempotencia**: Mensagens duplicadas sao detectadas e ignoradas (tanto no Autorizador quanto no Ledger)

---

## Decisoes Tecnicas

### Por que Hyperf?
- Performance superior via Swoole (coroutines nativas)
- Suporte nativo a AMQP consumers como processos Swoole
- Connection pooling para MySQL, Redis e RabbitMQ
- Ideal para servicos de alta vazao

### Concorrencia (SELECT FOR UPDATE)
Pessimistic locking com `SELECT FOR UPDATE` no debito de saldo:
- Lock na linha da conta durante a transacao
- Garante atomicidade mesmo com mensagens concorrentes
- Duracao minima do lock (~5-10ms)

### Anti-Fraude
Duas regras implementadas com Redis:
- **Valor maximo**: Bloqueia transacoes acima de R$ 10.000,00
- **Frequencia excessiva**: Bloqueia 3+ transacoes em 60 segundos para o mesmo cartao (usa Redis Sorted Set com precisao de microssegundos)

### Event Sourcing no Ledger
- Tabela `eventos_contabeis` e o event store imutavel
- Tabela `saldo_atual` e uma projecao desnormalizada (pode ser recalculada a partir dos eventos)
- Seeds criam contas com evento inicial `credito_realizado`

---

## Estrutura do Projeto

```
services/
  gateway/                # Microsservico de entrada
    app/
      Controller/         # TransacaoController, EstornoController
      Validation/         # Regras de validacao de input
      Amqp/Producer/      # TransacaoRecebidaProducer, EstornoSolicitadoProducer
      Exception/          # Exceptions de validacao
    config/
      routes.php          # Rotas HTTP
      autoload/amqp.php   # Config RabbitMQ

  autorizador/            # Microsservico de autorizacao
    app/
      Amqp/Consumer/      # TransacaoRecebidaConsumer, EstornoSolicitadoConsumer
      Amqp/Producer/      # TransacaoAprovada/Negada, EstornoAprovado/Negado
      Service/            # AutorizadorService, EstornoService, ContaService, AntiFraudeService
      Exception/          # SaldoInsuficiente, FrequenciaExcessiva, EstornoNaoPermitido, etc.
      Command/            # SeedContasCommand
    migrations/           # contas, transacoes_processadas

  ledger/                 # Microsservico de contabilidade
    app/
      Amqp/Consumer/      # TransacaoAprovadaConsumer, EstornoAprovadoConsumer
      Service/            # LedgerService (event sourcing)
      Model/              # EventoContabil, SaldoAtual
      Controller/         # ContaController (saldo, extrato)
      Command/            # SeedContasCommand
    migrations/           # saldo_atual, eventos_contabeis

shared/                   # Biblioteca compartilhada (path repository)
  src/Event/
    TransacaoRecebida.php
    TransacaoAprovada.php
    TransacaoNegada.php

docker/
  Dockerfile              # Imagem de desenvolvimento
  Dockerfile.prod         # Imagem de producao (multi-stage)
  init.sql                # Criacao dos bancos

k8s/                      # Manifests Kubernetes
  shared-config.yaml      # ConfigMap com variaveis compartilhadas
  mysql.yaml              # Deployment + Service + PVC
  rabbitmq.yaml           # Deployment + Service
  redis.yaml              # Deployment + Service
  gateway.yaml            # Deployment + Service (NodePort)
  autorizador.yaml        # Deployment + Service (ClusterIP)
  ledger.yaml             # Deployment + Service (ClusterIP)

docker-compose.yml        # Orquestracao local
```

---

## Componentes Principais

### 1. Gateway (Presentation Layer)
- **TransacaoController**: Recebe POST, valida input, gera UUID, publica evento no RabbitMQ e retorna `202 Accepted`
- **TransacaoValidator**: Valida `cartao_numero` (16 digitos), `valor` (inteiro > 0, em centavos) e `comerciante` (string obrigatoria)

### 2. Autorizador (Authorization Layer)
- **TransacaoRecebidaConsumer**: Consome mensagens da fila `autorizador.transacao_recebida`
- **AutorizadorService**: Orquestra o fluxo — verifica idempotencia, aplica anti-fraude, debita saldo, publica resultado
- **AntiFraudeService**: Valida valor maximo (R$ 10.000) e frequencia (max 3 transacoes/minuto por cartao via Redis)
- **ContaService**: Debita saldo com lock pessimista (`SELECT FOR UPDATE`)

### 3. Ledger (Accounting Layer)
- **TransacaoAprovadaConsumer**: Consome mensagens da fila `ledger.transacao_aprovada`
- **LedgerService**: Verifica idempotencia, registra `EventoContabil` imutavel, atualiza projecao `SaldoAtual`
- **ContaController**: Endpoints de consulta — `GET /conta/{uuid}/saldo` e `GET /conta/{uuid}/extrato`

### 4. Shared (Event DTOs)
- **TransacaoRecebida**: `transacao_id`, `cartao_mascarado`, `valor`, `comerciante`, `timestamp`
- **TransacaoAprovada**: mesmos campos + `saldo_restante`
- **TransacaoNegada**: `transacao_id`, `motivo`, `timestamp`
- **EstornoSolicitado**: `transacao_id`, `timestamp`
- **EstornoAprovado**: `transacao_id`, `cartao_mascarado`, `valor`, `comerciante`, `saldo_restante`, `timestamp`
- **EstornoNegado**: `transacao_id`, `motivo`, `timestamp`

### 5. Infraestrutura
- **RabbitMQ**: Exchange `transacoes` (topic) com bindings para filas dos consumers
- **MySQL**: 3 bancos isolados (`fintech_gateway`, `fintech_autorizador`, `fintech_ledger`)
- **Redis**: Tracking de frequencia anti-fraude via Sorted Set

---

## Banco de Dados

### Autorizador (`fintech_autorizador`)

| Tabela | Descricao |
|--------|-----------|
| `contas` | Cartao mascarado, saldo em centavos |
| `transacoes_processadas` | Idempotencia — `transacao_id` + resultado (`aprovada`/`negada`/`estornada`) + detalhes (valor, cartao, comerciante) |

### Ledger (`fintech_ledger`)

| Tabela | Descricao |
|--------|-----------|
| `eventos_contabeis` | Event store imutavel — tipo (`debito_realizado`/`estorno_realizado`), valor, comerciante, transacao_id |
| `saldo_atual` | Projecao de saldo — uuid da conta, cartao mascarado, saldo |

---

## Primeiros Passos

### Pre-requisitos

- **Docker** e **Docker Compose**
- **Minikube** e **kubectl** (para deploy em Kubernetes)

### Rodando com Docker Compose

1. Clone o repositorio:
```bash
git clone https://github.com/seu-usuario/fintech-lab.git && cd fintech-lab
```

2. Suba os containers:
```bash
docker compose up -d
```

3. Rode as migrations:
```bash
docker compose exec autorizador php bin/hyperf.php migrate
docker compose exec ledger php bin/hyperf.php migrate
```

4. Seede as contas de teste:
```bash
docker compose exec autorizador php bin/hyperf.php seed:contas
docker compose exec ledger php bin/hyperf.php seed:contas
```

5. Pronto! O Gateway esta disponivel em `http://localhost:9501`

### Rodando com Kubernetes (Minikube)

1. Inicie o Minikube:
```bash
minikube start
```

2. Aponte o Docker para o Minikube:
```bash
eval $(minikube docker-env)
```

3. Builde as imagens (use config limpa se tiver erro de credential):
```bash
DOCKER_CONFIG=/tmp/docker-minikube docker build -f docker/Dockerfile.prod --build-arg SERVICE=gateway -t fintech-lab/gateway:latest .
DOCKER_CONFIG=/tmp/docker-minikube docker build -f docker/Dockerfile.prod --build-arg SERVICE=autorizador -t fintech-lab/autorizador:latest .
DOCKER_CONFIG=/tmp/docker-minikube docker build -f docker/Dockerfile.prod --build-arg SERVICE=ledger -t fintech-lab/ledger:latest .
```

4. Aplique os manifests:
```bash
kubectl apply -f k8s/
```

5. Espere os pods ficarem prontos:
```bash
kubectl get pods -w
```

6. Crie as tabelas (o comando `migrate` nao existe na imagem de producao):
```bash
kubectl exec deploy/mysql -- mysql -uroot -proot fintech_autorizador -e "
CREATE TABLE IF NOT EXISTS contas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cartao_mascarado VARCHAR(19) NOT NULL UNIQUE,
  saldo BIGINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
);
CREATE TABLE IF NOT EXISTS transacoes_processadas (
  transacao_id VARCHAR(36) PRIMARY KEY,
  resultado VARCHAR(10) NOT NULL,
  valor BIGINT NULL,
  cartao_mascarado VARCHAR(19) NULL,
  comerciante VARCHAR(255) NULL,
  processada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);"

kubectl exec deploy/mysql -- mysql -uroot -proot fintech_ledger -e "
CREATE TABLE IF NOT EXISTS saldo_atual (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uuid VARCHAR(36) NOT NULL UNIQUE,
  cartao_mascarado VARCHAR(19) NOT NULL UNIQUE,
  saldo BIGINT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NULL
);
CREATE TABLE IF NOT EXISTS eventos_contabeis (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conta_id BIGINT UNSIGNED NOT NULL,
  tipo VARCHAR(30) NOT NULL,
  transacao_id VARCHAR(36) NOT NULL,
  valor BIGINT NOT NULL,
  comerciante VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (conta_id) REFERENCES saldo_atual(id),
  INDEX idx_conta_id (conta_id),
  UNIQUE INDEX eventos_contabeis_transacao_id_tipo_unique (transacao_id, tipo)
);"
```

7. Seede as contas:
```bash
kubectl exec deploy/autorizador -- php /opt/www/bin/hyperf.php seed:contas
kubectl exec deploy/ledger -- php /opt/www/bin/hyperf.php seed:contas
```

8. Teste de dentro do pod:
```bash
kubectl exec deploy/gateway -- curl -s -X POST http://localhost:9501/transacao \
  -H "Content-Type: application/json" \
  -d '{"cartao_numero":"4111111111111234","valor":5000,"comerciante":"Loja K8s"}'
```

---

## Portas

| Servico | Docker Compose | Kubernetes |
|---------|---------------|------------|
| Gateway | localhost:9501 | NodePort 30001 |
| Autorizador | localhost:9502 | ClusterIP (interno) |
| Ledger | localhost:9503 | ClusterIP (interno) |
| RabbitMQ (AMQP) | localhost:5672 | ClusterIP (interno) |
| RabbitMQ (UI) | localhost:15672 | `kubectl port-forward svc/rabbitmq 15672:15672` |
| MySQL | localhost:3306 | ClusterIP (interno) |
| Redis | localhost:6379 | ClusterIP (interno) |

---

## Endpoints Disponiveis

| Metodo | Servico | Endpoint | Descricao |
|--------|---------|----------|-----------|
| POST | Gateway | `/transacao` | Envia uma transacao para processamento |
| POST | Gateway | `/estorno` | Solicita estorno de uma transacao aprovada |
| GET | Ledger | `/conta/{uuid}/saldo` | Consulta saldo de uma conta |
| GET | Ledger | `/conta/{uuid}/extrato` | Consulta extrato (historico de eventos) |

---

## Contas de Teste

Os seeders criam 3 contas para testes:

| Cartao Mascarado | Numero para Input | Saldo Inicial | Em Reais |
|------------------|-------------------|---------------|----------|
| `**** **** **** 1234` | `4111111111111234` | 50.000.000 centavos | R$ 500.000 |
| `**** **** **** 5678` | `4111111111115678` | 10.000.000 centavos | R$ 100.000 |
| `**** **** **** 9012` | `4111111111119012` | 1.000.000 centavos | R$ 10.000 |

---

## Testando com cURL

### Transacao aprovada
```bash
curl -X POST http://localhost:9501/transacao \
  -H "Content-Type: application/json" \
  -d '{
    "cartao_numero": "4111111111111234",
    "valor": 15050,
    "comerciante": "Padaria do Joao"
  }'
```

Resposta esperada (`202 Accepted`):
```json
{
  "transacao_id": "uuid-gerado",
  "status": "processando"
}
```

### Saldo insuficiente
```bash
curl -X POST http://localhost:9501/transacao \
  -H "Content-Type: application/json" \
  -d '{
    "cartao_numero": "4111111111119012",
    "valor": 2000000,
    "comerciante": "Loja Cara"
  }'
```
A transacao sera negada pelo Autorizador (saldo insuficiente). Resultado visivel nos logs.

### Valor acima do limite (R$ 10.000)
```bash
curl -X POST http://localhost:9501/transacao \
  -H "Content-Type: application/json" \
  -d '{
    "cartao_numero": "4111111111111234",
    "valor": 1500000,
    "comerciante": "Joalheria"
  }'
```
Negada pelo anti-fraude (valor excede R$ 10.000).

### Frequencia excessiva (3+ em 1 minuto)
```bash
# Envie 3 transacoes rapidamente para o mesmo cartao
for i in 1 2 3; do
  curl -s -X POST http://localhost:9501/transacao \
    -H "Content-Type: application/json" \
    -d '{"cartao_numero":"4111111111115678","valor":100,"comerciante":"Loja Rapida"}'
  echo ""
done
```
A terceira sera negada por frequencia excessiva.

### Validacao de input
```bash
# Cartao invalido
curl -X POST http://localhost:9501/transacao \
  -H "Content-Type: application/json" \
  -d '{"cartao_numero": "1234", "valor": 100, "comerciante": "Loja"}'
```
Resposta (`422`):
```json
{
  "erros": ["cartao_numero deve ter 16 digitos numericos."]
}
```

```bash
# Valor zero
curl -X POST http://localhost:9501/transacao \
  -H "Content-Type: application/json" \
  -d '{"cartao_numero": "4111111111111234", "valor": 0, "comerciante": "Loja"}'
```
Resposta (`422`):
```json
{
  "erros": ["valor deve ser pelo menos 1."]
}
```

### Solicitar estorno
```bash
# Use o transacao_id retornado pelo POST /transacao
curl -X POST http://localhost:9501/estorno \
  -H "Content-Type: application/json" \
  -d '{
    "transacao_id": "uuid-da-transacao"
  }'
```

Resposta esperada (`202 Accepted`):
```json
{
  "transacao_id": "uuid-da-transacao",
  "status": "estorno_solicitado"
}
```
O Autorizador verifica se a transacao existe e foi aprovada. Se sim, credita o saldo e publica `EstornoAprovado`. Tentar estornar novamente retorna estorno negado (ja estornada).

### Consultar saldo (Ledger)
```bash
# Substitua {uuid} pelo UUID da conta no Ledger
curl http://localhost:9503/conta/{uuid}/saldo
```

### Consultar extrato (Ledger)
```bash
curl http://localhost:9503/conta/{uuid}/extrato
```

---

## Comandos Uteis

```bash
# Logs de um servico
docker compose logs -f gateway
docker compose logs -f autorizador
docker compose logs -f ledger

# Acessar RabbitMQ Management UI
# Usuario: guest / Senha: guest
# Docker Compose: http://localhost:15672
# K8s: kubectl port-forward svc/rabbitmq 15672:15672 → http://localhost:15672

# Reiniciar um servico
docker compose restart gateway

# Parar tudo
docker compose down

# K8s: ver estado do cluster
kubectl get pods,svc,pvc

# K8s: logs
kubectl logs deploy/autorizador

# K8s: shell interativo
kubectl exec -it deploy/gateway -- sh

# K8s: derrubar tudo
kubectl delete -f k8s/
```

---

## Tratamento de Erros

| Exception | Servico | Descricao |
|-----------|---------|-----------|
| `CartaoInvalidoException` | Gateway | Cartao nao tem 16 digitos |
| `ValorInvalidoException` | Gateway | Valor nao e inteiro positivo |
| `ComercianteInvalidoException` | Gateway | Comerciante vazio |
| `SaldoInsuficienteException` | Autorizador | Saldo menor que o valor da transacao |
| `FrequenciaExcessivaException` | Autorizador | 3+ transacoes em 60s para o mesmo cartao |
| `ValorExcessivoException` | Autorizador | Valor acima de R$ 10.000 |
| `ContaNaoEncontradaException` | Autorizador/Ledger | Cartao nao cadastrado |
| `TransacaoDuplicadaException` | Autorizador | Mensagem duplicada (idempotencia) |
| `EstornoNaoPermitidoException` | Autorizador | Transacao nao encontrada, negada ou ja estornada |
| `EventoDuplicadoException` | Ledger | Evento duplicado (idempotencia) |

---

## Proximos Passos

### Observabilidade (Prometheus + Grafana)
Adicionar metricas aos servicos (requests/s, latencia, taxa de erros, uso de recursos) com dashboards visuais. Permitiria monitorar a saude do pipeline em tempo real e identificar gargalos.

### CI/CD (GitHub Actions)
Automatizar o pipeline de deploy: push no GitHub → roda testes → builda imagens → faz deploy no Kubernetes. Eliminaria passos manuais de build e apply.

### Ingress Controller
Substituir `kubectl port-forward` por um Ingress que expoe os servicos com URLs reais (ex: `gateway.fintech.local`). Mais proximo de como funciona em producao, com roteamento por path ou hostname.

### Resource Limits e Autoscaling
Definir `requests` e `limits` de CPU/memoria nos pods, e configurar Horizontal Pod Autoscaler (HPA) para escalar replicas automaticamente baseado em carga.

### Secrets
Migrar senhas do ConfigMap para Kubernetes Secrets, separando configuracao publica de dados sensiveis.

### Testes de Integracao
Teste end-to-end que publica uma mensagem no RabbitMQ e verifica que o evento chegou no Ledger. Validaria o pipeline completo (Gateway → Autorizador → Ledger) de forma automatizada.

### Health Check Endpoints
Expor endpoints de health (`/health`) nos servicos Hyperf e configurar liveness/readiness probes nos manifests do Kubernetes apontando pra eles, em vez de TCP socket. Permite verificacoes mais inteligentes (ex: checar conexao com MySQL e RabbitMQ).
