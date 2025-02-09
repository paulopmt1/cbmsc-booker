# cbmsc-booker
Sistema para gestão de horas de bombeiros voluntários para uso pelo CBMSC de Videira


## Ambiente de desenvolvimento

O ambiente de desenvolvimento é feito com Docker.

### Docker
Tenha o Docker e docker-compose instalado e rodando.

Em sua máquina, abra o terminal e rode o comando:

```bash
docker compose up -d
```

Agora entre no container da aplicação:

```bash
docker exec -it cbmsc_booker_web bash
```

Instale as dependências do projeto (rodando dentro do container):

```bash
composer install
```

### Acesse a aplicação

Acesse a aplicação no navegador: http://localhost:8084

![image](https://github.com/user-attachments/assets/be0579e4-6e49-424a-9e45-a70fcc43bdc7)

### Acesse o phpmyadmin

Acesse o phpmyadmin no navegador: http://localhost:8085

![image](https://github.com/user-attachments/assets/5bd7fb66-4c3b-4313-b9f7-b1ffca458231)

