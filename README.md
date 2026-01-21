# cbmsc-booker
Sistema para gestão de horas de bombeiros voluntários para uso pelo CBMSC de Videira


## Ambiente de desenvolvimento

O ambiente de desenvolvimento é feito com Docker.

### Fazendo setup com docker
Tenha o Docker e docker-compose instalado e rodando.

1. Em sua máquina, abra o terminal e rode o comando: `docker compose up -d`
2. Agora entre no container da aplicação: `docker exec -it cbmsc_booker_web bash`
3. Instale as dependências do projeto (rodando dentro do container): `composer install`
4. Instale os pacotes frontend: `yarn install`
5. Rode a aplicação node do ambiente `dev` com: `npm run dev-server`

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

O arquivo `.env` deve possuir o path para o arquivo `cbmsc-booker-credentials.json`. Aqui um exemplo de path:

```
GOOGLE_AUTH_CONFIG=/var/www/html/config/cbmsc-booker-credentials.json
```

---

## Deploy em Produção

O deploy em produção utiliza Docker com configurações otimizadas.

### Pré-requisitos

- Docker e docker-compose instalados no servidor
- Arquivo `cbmsc-booker-credentials.json` (credenciais do Google Service Account)

### Passo 1: Configurar credenciais do Google

Copie o arquivo de credenciais para a pasta `secrets/` do projeto:

```bash
mkdir -p ./secrets
cp cbmsc-booker-credentials.json ./secrets/
chmod 600 ./secrets/cbmsc-booker-credentials.json
```

> **Nota:** A pasta `secrets/` está no `.gitignore` e não será commitada.

### Passo 2: Configurar variáveis de ambiente

Defina as variáveis de ambiente necessárias:

```bash
export MYSQL_ROOT_PASSWORD="sua-senha-root-segura"
export MYSQL_DATABASE="cbmsc_booker"
export MYSQL_USER="cbmsc_user"
export MYSQL_PASSWORD="sua-senha-segura"
export APP_SECRET="$(openssl rand -hex 32)"
```

> **Dica:** Para persistir as variáveis, adicione-as ao arquivo `/etc/environment` ou crie um script de inicialização.

### Passo 3: Executar o deploy

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
