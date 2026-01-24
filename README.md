# cbmsc-booker
Sistema para gestão de horas de bombeiros voluntários para uso pelo CBMSC de Videira


## Ambiente de desenvolvimento

O ambiente de desenvolvimento é feito com Docker.

### Fazendo setup com docker
Tenha o Docker e docker-compose instalado e rodando.

1. Copie o arquivo de credenciais do aplicativo do google dentro da pasta `secrets` que não é versionada

2. Popule a variável de ambiente GOOGLE_CREDENTIALS_JSON com esse comando (a partir do host): `export GOOGLE_CREDENTIALS_JSON=$(cat secrets/cbmsc-booker-credentials.json)`

3. Em sua máquina, abra o terminal e rode o comando: `docker compose up -d`
4. Agora entre no container da aplicação: `docker exec -it cbmsc_booker_web bash`
5. Instale as dependências do projeto (rodando dentro do container): `composer install`
6. Instale os pacotes frontend: `yarn install`
7. Rode a aplicação node do ambiente `dev` com: `npm run dev-server`

### Acesse a aplicação

Acesse a aplicação no navegador: http://localhost:8084

<img width="2168" height="1652" alt="image" src="https://github.com/user-attachments/assets/d694e437-7666-4dec-917a-a564a73ff4fd" />


### Acesse debugger para algoritmo de priorização

Esse debugger nos ajuda a validar a eficácia e comportamento do algoritmo de ordenação. Acesse ele aqui: http://localhost:8084/consulta_escala_por_dia/PLANILHA_ID/DIA?withCache=true&cotasPorDia=2.5&diasSelecionados=3,4,10,11,17,18,24,25

Resultado esperado:

<img width="956" height="825" alt="image" src="https://github.com/user-attachments/assets/e6f91ea1-a5b6-470b-8256-1756816fab10" />


### Acesse o phpmyadmin

Acesse o phpmyadmin no navegador: http://localhost:8085

![image](https://github.com/user-attachments/assets/5bd7fb66-4c3b-4313-b9f7-b1ffca458231)

### Como rodar testes

1. Entre no docker container: `docker exec -it cbmsc_booker_web /bin/bash`
2. Rode o teste: `./vendor/bin/phpunit tests/Service/CalculadorDePontosTest.php`

<img width="1121" height="348" alt="image" src="https://github.com/user-attachments/assets/fb3b6c3a-ea3f-4478-9747-cd7884e45676" />



## Como sincronizar dados

A planilha de origem deve:

- Estar com acesso semi-público, ou seja, qualquer um com o link deve poder visualizar.


A planilha de destino deve:
- Estar com o email do robô tendo permissão de edição (planilha-rob@cbmsc-booker.iam.gserviceaccount.com)

A variável de ambiente `GOOGLE_CREDENTIALS_JSON` deve conter o conteúdo JSON das credenciais do Google Service Account. Exemplo:

```bash
export GOOGLE_CREDENTIALS_JSON='{"type":"service_account","project_id":"...","private_key":"..."}'
```

---

## Deploy em Produção

O deploy em produção utiliza Docker com configurações otimizadas.

### Pré-requisitos

- Docker e docker-compose instalados no servidor
- Credenciais JSON do Google Service Account

### Passo 1: Configurar variáveis de ambiente

Defina as variáveis de ambiente necessárias:

```bash
export APP_SECRET="$(openssl rand -hex 32)"
export CPF_DO_QUERUBIN=000000000 (somente números do CPF do Querubin)

# Credenciais do Google (conteúdo JSON do arquivo de service account)
export GOOGLE_CREDENTIALS_JSON='{"type":"service_account","project_id":"cbmsc-booker","private_key_id":"...","private_key":"-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n","client_email":"planilha-rob@cbmsc-booker.iam.gserviceaccount.com","client_id":"...","auth_uri":"https://accounts.google.com/o/oauth2/auth","token_uri":"https://oauth2.googleapis.com/token","auth_provider_x509_cert_url":"https://www.googleapis.com/oauth2/v1/certs","client_x509_cert_url":"..."}'
```

> **Dica:** Você pode carregar o JSON de um arquivo com: `export GOOGLE_CREDENTIALS_JSON=$(cat cbmsc-booker-credentials.json)`

> **Dica:** Para persistir as variáveis, adicione-as ao arquivo `/etc/environment` ou crie um script de inicialização.

### Passo 2: Executar o deploy

```bash
./deploy.sh
```

A aplicação estará disponível em: `http://seu-servidor:5001`

### Configuração com HTTPS (Opcional, Recomendado)

Para configurar HTTPS com Nginx e Let's Encrypt:

1. Instale Nginx e Certbot:
   ```bash
   sudo apt install nginx certbot python3-certbot-nginx
   ```

2. Copie a configuração do Nginx:
   ```bash
   sudo cp nginx.prod.conf /etc/nginx/sites-available/cbmsc-booker
   sudo ln -s /etc/nginx/sites-available/cbmsc-booker /etc/nginx/sites-enabled/
   ```

3. Edite o arquivo e substitua `YOUR_DOMAIN` pelo seu domínio:
   ```bash
   sudo nano /etc/nginx/sites-available/cbmsc-booker
   ```

4. Obtenha o certificado SSL:
   ```bash
   sudo certbot --nginx -d seu-dominio.com
   ```

5. Reinicie o Nginx:
   ```bash
   sudo systemctl reload nginx
   ```

### Comandos úteis

```bash
# Ver logs da aplicação
docker compose -f docker-compose.prod.yml logs -f

# Parar a aplicação
docker compose -f docker-compose.prod.yml down

# Reiniciar a aplicação
docker compose -f docker-compose.prod.yml restart

# Reconstruir e reiniciar
docker compose -f docker-compose.prod.yml up -d --build
``` 
