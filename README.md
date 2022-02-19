# Arion PHP Light Framework

## Pré-requisitos

- PHP 7.2+
- Apache HTTP Server

## Estrutura de arquivos e diretórios

A estrutura completa padrão dos diretórios de um projeto Arion é esta:

```
arion/            Core do Arion Framework
app/              Recursos do projeto
    database/     Modelagem do banco de dados (migrations)
    modules/      Módulos (controllers)
    libs/         Bibliotecas (helpers)
config/           Configurações do projeto
    app.yml       Configurações da aplicação
    database.yml  Configurações do banco de dados
    install.yml   Apontamentos para instalação de módulos e bibliotecas do projeto
routes/           Rotas HTTP do projeto
.htaccess         Arquivo de configuração distribuída do Apache     
mason             Arquivo de execução via terminal para gestão do projeto
index.php         Arquivo que irá instanciar o Arion
```

## Configurações do projeto

As configurações do projeto estão contidas em ***/project.yml***.

> **IMPORTANTE**: O arquivo project.yml deve possuir restrição de acesso externo em .htaccess

**FLOW**: Ordem de inserção padrão dos arquivos que compõem as ***Rotas*** do projeto.

Exemplo:
```
FLOW: 
  - /{ROUTE}.php
  - /header.tpl
  - /{ROUTE}.tpl
```
***{ROUTE}*** é uma variável de configuração, e será substituída pelo nome da rota ao ser invocada.

Logo, seguindo a configuração acima, ao buscar a rota ***site.com/teste*** o browser irá retornar uma página contendo a seguinte ordem de inserção de arquivos:
```
<?php
include "routes/teste.php";
include "routes/header.tpl";
include "routes/teste.tpl";
```

## Rotas HTTP

Arion usa file-system routing, então as rotas obedecem a estrutura de diretórios existentes em ***/routes***.

Exemplos de arquivos de rotas e seus endereços finais na web:

```
/routes/index/index.php             > site.com
/routes/contato/contato.php         > site.com/contato
/routes/sobre/sobre.php             > site.com/sobre
/routes/sobre/appixar.php           > site.com/sobre/appixar
```
Simples, né?

## Módulos e bibliotecas

**Bibliotecas** são ***helpers***, objetos ou funções ***curtas***, sem interação com o bancos de dados, que visam auxiliar as rotas do seu projeto.

**Módulos** são ***controllers***, conjunto de objetos ou funções que compõem um contexto específico.

> **Exemplo**: A função de "disparo de e-mail" seria uma biblioteca, enquanto um conjunto de funções de "autenticação de usuário" seria um módulo.

A composição de ambas seguem o mesmo padrão:
```
# Exemplo de módulo 'my-auth-module':

modules/
    my-auth-module/
        autoload.php


# Exemplo de biblioteca 'helpers':

libs/
    helpers/
        autoload.php
```

### Invocando módulos e bibliotecas

Suponha que você tenha uma biblioteca chamada ***helpers*** contendo várias funções úteis para o projeto. Exemplo:

```
# /libs/helpers/autoload.php

<?php

function validaEmail($email) { ... }

function validaCPF($cpf) { ... }
```
Para a rota ***register*** invocar esta biblioteca é simples:
```
# /routes/register.php

<?php

arion::lib('helpers');

if (validaEmail($email)) {...}
```