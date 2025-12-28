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

<img width="1441" height="826" alt="image" src="https://github.com/user-attachments/assets/f77d86b5-48eb-4381-a4c5-6ab76c7882c4" />


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
