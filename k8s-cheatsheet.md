# Kubernetes Cheatsheet — Fintech Lab

## Entendendo o `eval $(minikube docker-env)`

O Minikube roda um cluster Kubernetes dentro de uma VM/container Docker.
Essa VM tem seu **proprio Docker daemon**, separado do Docker do seu computador.

Quando voce faz `docker build` normalmente, a imagem fica no Docker do **seu computador**.
Mas o Kubernetes do Minikube nao enxerga essas imagens — ele so ve as do Docker **dele**.

O comando `eval $(minikube docker-env)` reconfigura seu terminal pra apontar
pro Docker **dentro do Minikube**. Assim, qualquer `docker build` que voce rodar
vai construir a imagem direto onde o Kubernetes consegue usar.

```bash
# ANTES: seu terminal aponta pro Docker do seu computador
docker images   # imagens do SEU Docker

# Aponta pro Docker do Minikube (so vale pra esse terminal!)
eval $(minikube docker-env)

# DEPOIS: seu terminal aponta pro Docker do Minikube
docker images   # imagens do Docker do MINIKUBE

# No WSL2, precisa desse workaround pra evitar erro de credencial:
export DOCKER_CONFIG=/tmp/docker-minikube
mkdir -p /tmp/docker-minikube
echo '{}' > /tmp/docker-minikube/config.json

# Agora sim, build funciona:
docker build -f docker/Dockerfile --build-arg SERVICE=gateway -t fintech/gateway:latest .

# Pra voltar ao Docker normal do seu computador:
eval $(minikube docker-env --unset)
```

**Importante**: o `eval` so afeta o terminal atual. Se abrir outro terminal,
precisa rodar de novo.

---

## Iniciar / Parar o Minikube

```bash
minikube start              # Inicia o cluster
minikube stop               # Para (preserva estado)
minikube delete             # Apaga tudo (limpa do zero)
minikube status             # Verifica se ta rodando
minikube dashboard          # Abre UI web do Kubernetes no browser
```

---

## Build das imagens (dentro do Minikube)

```bash
# Sempre rodar isso ANTES de buildar:
eval $(minikube docker-env)
export DOCKER_CONFIG=/tmp/docker-minikube

# Build de cada servico:
docker build -f docker/Dockerfile --build-arg SERVICE=gateway -t fintech/gateway:latest .
docker build -f docker/Dockerfile --build-arg SERVICE=autorizador -t fintech/autorizador:latest .
docker build -f docker/Dockerfile --build-arg SERVICE=ledger -t fintech/ledger:latest .

# Aplicar os manifests:
kubectl apply -f k8s/
```

---

## Comandos do dia a dia

### Pods
```bash
kubectl get pods                        # Lista todos os pods
kubectl get pods -o wide                # Com IP e node
kubectl get pods -l app=autorizador     # Filtra por label
kubectl describe pod <nome-do-pod>      # Detalhes completos de um pod
kubectl logs <nome-do-pod>              # Ver logs de um pod
kubectl logs deployment/gateway         # Logs do deployment (pega um pod)
kubectl logs -f deployment/gateway      # Logs em tempo real (follow)
kubectl logs -l app=autorizador         # Logs de TODOS os pods com essa label
```

### Exec (entrar no pod)
```bash
kubectl exec deployment/gateway -- ls /opt/www       # Roda um comando
kubectl exec deployment/gateway -- php -v             # Ver versao do PHP
kubectl exec -it deployment/gateway -- sh             # Shell interativo
```

### Deployments
```bash
kubectl get deployments                              # Lista deployments
kubectl describe deployment autorizador              # Detalhes do deployment
kubectl rollout restart deployment/gateway           # Reinicia pods (apos mudanca de codigo)
kubectl rollout status deployment/gateway            # Acompanha o rollout
```

### Scaling
```bash
kubectl scale deployment autorizador --replicas=3    # Escala pra 3
kubectl scale deployment autorizador --replicas=1    # Volta pra 1
kubectl scale deployment autorizador --replicas=0    # Para todos (0 pods)
```

### Services e ConfigMaps
```bash
kubectl get services                                 # Lista services
kubectl get configmap shared-config -o yaml          # Ver variaveis de ambiente
```

### Events
```bash
kubectl get events --sort-by='.lastTimestamp'        # Historico do cluster
```

### Port Forward (acessar servicos do Minikube no localhost)
```bash
kubectl port-forward service/gateway 9501:9501       # Gateway em localhost:9501
kubectl port-forward service/rabbitmq 15672:15672    # RabbitMQ UI em localhost:15672
kubectl port-forward service/mysql 3306:3306         # MySQL em localhost:3306

# Rodar em background (adicionar & no final):
kubectl port-forward service/gateway 9501:9501 &

# Parar todos os port-forwards:
kill $(jobs -p)
```

---

## Truques uteis

### Ver qual pod processou uma mensagem
```bash
# Escala pra 3 e veja nos logs:
kubectl scale deployment autorizador --replicas=3
kubectl logs -l app=autorizador --prefix=true -f
# O --prefix mostra o nome do pod antes de cada linha de log
```

### Testar uma transacao
```bash
curl -s -X POST http://localhost:9501/transacao \
  -H "Content-Type: application/json" \
  -d '{"cartao_numero":"5500000000000001","valor":1050,"comerciante":"Cafe Debug"}'
```

### Ver filas do RabbitMQ (sem UI)
```bash
kubectl exec deployment/rabbitmq -- rabbitmqctl list_queues name messages consumers
```

### Ver consumers conectados
```bash
kubectl exec deployment/rabbitmq -- rabbitmqctl list_consumers
```

### Seed das contas (necessario apos criar o banco)
```bash
kubectl exec deployment/autorizador -- php bin/hyperf.php seed:contas
kubectl exec deployment/ledger -- php bin/hyperf.php seed:contas
```

---

## Rolling Update (deploy sem downtime)

Quando voce muda o codigo e rebuilda a imagem, o Kubernetes troca os pods
**um por um**, sem derrubar o servico. Ele sobe um pod novo, espera o
readiness probe confirmar que ta saudavel, e so entao mata o antigo.

```bash
# 1. Altere o codigo do servico
# 2. Rebuilde a imagem (com eval do minikube docker-env ativo):
docker build -f docker/Dockerfile --build-arg SERVICE=gateway -t fintech/gateway:latest .

# 3. Reinicie o deployment (forca ele a puxar a imagem nova):
kubectl rollout restart deployment/gateway

# 4. Acompanhe o rollout ao vivo:
kubectl rollout status deployment/gateway
# Vai mostrar: "Waiting for deployment ... rollout to finish: 1 old replicas are pending termination"
# E depois: "deployment successfully rolled out"

# Se algo der errado, desfaz o rollout:
kubectl rollout undo deployment/gateway

# Ver historico de rollouts:
kubectl rollout history deployment/gateway
```

---

## Secrets (senhas seguras)

O ConfigMap guarda dados em texto puro — qualquer `kubectl get configmap -o yaml`
mostra tudo. Para senhas, use **Secrets** (base64 encoded, e em producao integra
com Vault, AWS Secrets Manager, etc).

```bash
# Criar um secret:
kubectl create secret generic db-credentials \
  --from-literal=DB_PASSWORD=senha-segura \
  --from-literal=DB_USERNAME=root

# Ver secrets (valores ficam em base64):
kubectl get secret db-credentials -o yaml

# Decodificar um valor:
kubectl get secret db-credentials -o jsonpath='{.data.DB_PASSWORD}' | base64 -d

# No manifest do deployment, trocar envFrom pra usar o secret:
# envFrom:
#   - configMapRef:
#       name: shared-config
#   - secretRef:
#       name: db-credentials      # <-- adiciona isso
```

**Importante**: Secrets do Kubernetes NAO sao criptografados por padrao,
sao apenas base64. Em producao, habilite encryption at rest ou use
solucoees externas (HashiCorp Vault, Sealed Secrets, etc).

---

## Resource Limits (CPU e memoria)

Sem limits, um pod pode consumir toda a memoria do node e derrubar os outros
pods. Sempre defina requests (minimo garantido) e limits (maximo permitido).

```yaml
# No manifest do deployment, dentro de containers[]:
resources:
  requests:          # Minimo garantido pro pod
    memory: "128Mi"  # 128 megabytes
    cpu: "100m"      # 100 milicores (0.1 CPU)
  limits:            # Maximo que o pod pode usar
    memory: "256Mi"  # Se passar, o pod é KILLED (OOMKilled)
    cpu: "500m"      # Se passar, é throttled (fica lento, mas nao morre)
```

```bash
# Ver consumo atual de recursos (precisa do metrics-server):
minikube addons enable metrics-server
kubectl top pods                         # CPU e memoria de cada pod
kubectl top nodes                        # CPU e memoria do node
```

**Dica**: `requests` é o que o scheduler usa pra decidir onde colocar o pod.
Se voce pedir `requests.memory: 2Gi` e o node so tem 1Gi livre, o pod fica
Pending (nao é agendado).

---

## Horizontal Pod Autoscaler (HPA)

Em vez de escalar manualmente com `kubectl scale`, o HPA monitora metricas
(CPU, memoria) e ajusta o numero de replicas automaticamente.

```bash
# Habilitar metrics-server (necessario pro HPA funcionar):
minikube addons enable metrics-server

# Criar um HPA pro autorizador:
kubectl autoscale deployment autorizador \
  --min=1 \
  --max=5 \
  --cpu-percent=70
# Se a media de CPU dos pods passar de 70%, cria mais replicas (ate 5)
# Se cair, remove replicas (ate 1)

# Ver status do HPA:
kubectl get hpa
# Mostra: TARGETS (uso atual vs threshold), MINPODS, MAXPODS, REPLICAS

# Detalhes do HPA:
kubectl describe hpa autorizador

# Remover o HPA:
kubectl delete hpa autorizador
```

**Importante**: pra o HPA funcionar, o deployment PRECISA ter `resources.requests`
definido. Sem isso, o HPA nao consegue calcular a porcentagem de uso.

---

## Proximos passos na jornada

### Observabilidade (Prometheus + Grafana)
Monitorar metricas dos servicos (requests/s, latencia, erros, uso de recursos)
com dashboards visuais. O Minikube tem addon pra isso:
```bash
minikube addons enable metrics-server
# Prometheus e Grafana geralmente sao instalados via Helm charts
```

### CI/CD (GitHub Actions)
Automatizar o pipeline: push no GitHub → roda testes → builda imagens →
faz deploy no Kubernetes automaticamente.

### Ingress Controller
Em vez de usar `port-forward` pra cada servico, um Ingress expoe tudo
com URLs reais (ex: `gateway.fintech.local`):
```bash
minikube addons enable ingress
# Depois cria um manifest Ingress que mapeia URLs pros services
```

---

## Fluxo completo: do zero ate rodando

```bash
# 1. Iniciar o Minikube
minikube start

# 2. Apontar Docker pro Minikube
eval $(minikube docker-env)
export DOCKER_CONFIG=/tmp/docker-minikube

# 3. Buildar imagens
docker build -f docker/Dockerfile --build-arg SERVICE=gateway -t fintech/gateway:latest .
docker build -f docker/Dockerfile --build-arg SERVICE=autorizador -t fintech/autorizador:latest .
docker build -f docker/Dockerfile --build-arg SERVICE=ledger -t fintech/ledger:latest .

# 4. Aplicar manifests
kubectl apply -f k8s/

# 5. Esperar tudo ficar pronto
kubectl wait --for=condition=ready pod --all --timeout=120s

# 6. Seed das contas
kubectl exec deployment/autorizador -- php bin/hyperf.php seed:contas
kubectl exec deployment/ledger -- php bin/hyperf.php seed:contas

# 7. Port-forward pra acessar
kubectl port-forward service/gateway 9501:9501 &
kubectl port-forward service/rabbitmq 15672:15672 &

# 8. Testar!
curl -s -X POST http://localhost:9501/transacao \
  -H "Content-Type: application/json" \
  -d '{"cartao_numero":"5500000000000001","valor":1050,"comerciante":"Teste"}'
```
