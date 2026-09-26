# Cost Visor — Sistema de Gestão Financeira Pessoal

## Sobre o projeto

O Cost Visor é uma aplicação web de uso pessoal para controle de gastos e gestão financeira. A proposta é permitir que o usuário registre, organize e acompanhe suas receitas e despesas, incluindo transações recorrentes e parceladas, e tenha uma visão consolidada da própria situação financeira por meio de indicadores, listagens e gráficos.

A primeira versão trabalha com **saldo financeiro global**. Não existe, nesta versão, separação por banco, carteira, conta corrente, cartão ou instituição financeira.

## Escopo da primeira versão

O sistema contempla:

- criação e gerenciamento de usuários;
- autenticação;
- gerenciamento de categorias;
- gerenciamento de receitas;
- gerenciamento de despesas;
- gerenciamento de transações recorrentes;
- gerenciamento de transações parceladas;
- consulta do histórico financeiro;
- consulta das transações por mês;
- indicadores financeiros;
- concentração de despesas por categoria.

### O que não faz parte desta versão

Não fazem parte da primeira versão:

- integração bancária;
- Open Finance;
- importação automática de extratos;
- sincronização com cartões de crédito;
- investimentos;
- controle de patrimônio;
- múltiplas moedas;
- pagamentos;
- compartilhamento de dados financeiros entre usuários;
- múltiplas contas financeiras;
- integração com serviços financeiros externos.

Esses itens podem ser considerados futuramente, mas não devem ser introduzidos durante a implementação atual.

## Organização do projeto

O projeto deve possuir dois diretórios na raiz:

```text
/
├── backend/
└── frontend/
```

### Backend

`/backend` contém uma aplicação Laravel responsável exclusivamente pela API REST.

Suas responsabilidades são:

- autenticação e autorização;
- validação dos dados;
- regras de negócio;
- persistência;
- processamento de recorrências;
- processamento de parcelamentos;
- cálculos financeiros;
- fornecimento dos dados consumidos pelo frontend.

O backend não deve possuir páginas ou interfaces HTML destinadas ao usuário final.

### Frontend

`/frontend` contém uma aplicação React + TypeScript.

Suas responsabilidades são:

- interface gráfica;
- navegação;
- comunicação com a API;
- apresentação dos dados;
- gerenciamento do estado da aplicação;
- feedback visual das operações;
- validações relacionadas à experiência do usuário.

As regras de negócio não devem ficar acopladas aos componentes visuais.

## Tecnologias

### Backend

- PHP
- Laravel
- API REST
- JWT
- banco de dados relacional

### Frontend

- React
- TypeScript
- shadcn/ui

A interface deve ser moderna, minimalista, responsiva, legível e acessível, com cor principal escura e elementos de contraste para facilitar a identificação das informações.

### Banco de dados

- SQLite

## Conceitos principais

### Usuário

Pessoa que possui uma conta no sistema.

### Categoria

Classificação utilizada para organizar transações. Cada categoria pertence a um único usuário e possui um tipo:

- `RECEITA`
- `DESPESA`

### Transação

Registro financeiro individual que representa uma receita ou despesa.

Uma transação possui:

- valor;
- descrição;
- data;
- tipo;
- categoria.

### Recorrência

Regra usada para gerar transações automaticamente de acordo com uma frequência.

As frequências previstas são:

- diária;
- semanal;
- mensal;
- anual.

Recorrências também podem utilizar uma regra baseada em dia útil.

### Dia útil

Para este projeto, dia útil é qualquer dia que **não seja domingo ou feriado**. Portanto, **sábado é considerado dia útil**.

O cálculo deve utilizar o calendário de feriados configurado pela aplicação.

### Parcelamento

Uma despesa dividida em múltiplas parcelas.

Cada parcela é registrada como uma transação individual e é contabilizada no mês correspondente à sua data. Ao mesmo tempo, as parcelas devem manter uma relação lógica que permita identificar a compra/parcelamento de origem.

### Saldo

O saldo é calculado por:

```text
saldo = receitas - despesas
```

Na primeira versão, existe apenas um saldo financeiro global por usuário.

## Funcionalidades

### Usuários e autenticação

O usuário pode:

- criar uma conta;
- realizar login com e-mail e senha;
- encerrar a sessão;
- consultar e atualizar nome e e-mail.

O cadastro exige nome, e-mail, senha e confirmação de senha. O e-mail deve ser válido e único. A senha deve atender aos requisitos mínimos de segurança e nunca pode ser armazenada em texto puro.

Após o login, a API emite um JWT, utilizado pelo frontend para acessar endpoints protegidos.

### Categorias

O usuário pode criar, listar, consultar, atualizar e excluir categorias.

Uma categoria possui nome, cor e tipo.

Uma categoria utilizada por transações não pode ser excluída. Além disso, categorias pertencentes a outros usuários nunca podem ser acessadas.

### Transações

O usuário pode criar, listar, consultar, atualizar e excluir transações.

O valor deve ser maior que zero. A descrição, data, tipo e categoria são obrigatórios.

A categoria deve pertencer ao usuário autenticado e seu tipo deve ser compatível com o tipo da transação:

```text
RECEITA → categoria RECEITA
DESPESA → categoria DESPESA
```

### Recorrências

O usuário pode configurar transações recorrentes com:

- valor;
- descrição;
- tipo;
- categoria;
- frequência;
- data inicial;
- data final opcional.

O sistema deve gerar as transações de acordo com essa configuração.

Quando a recorrência utilizar dia útil, domingos e feriados não contam; sábados contam.

### Parcelamentos

O usuário pode registrar uma despesa parcelada informando:

- valor total;
- quantidade de parcelas;
- data da primeira parcela;
- descrição;
- categoria.

Cada parcela vira uma transação individual. A contabilização ocorre no mês correspondente à data de cada parcela.

### Consulta de transações

A listagem deve permitir:

- filtro por mês;
- filtro por período;
- filtro por tipo;
- filtro por categoria;
- busca por descrição;
- ordenação por data;
- paginação.

A ordenação padrão é da data mais recente para a mais antiga.

Receitas e despesas devem ser diferenciadas visualmente.

### Dashboard

O dashboard apresenta, por padrão, o mês corrente e permite selecionar outro mês.

O período selecionado deve ser aplicado aos indicadores, gráficos e listagens do dashboard.

O painel deve apresentar:

- saldo do período;
- total de receitas;
- total de despesas;
- quantidade de transações;
- transações do período;
- concentração de despesas por categoria.

O saldo é:

```text
receitas - despesas
```

A concentração de despesas utiliza:

```text
percentual =
(soma das despesas da categoria / soma total das despesas) × 100
```

Quando não houver despesas, o frontend deve apresentar um estado vazio e nenhum cálculo pode resultar em divisão por zero.

## Segurança e isolamento

Todos os dados financeiros pertencem a um usuário.

Conhecer o identificador de um recurso pertencente a outro usuário não pode permitir seu acesso.

O backend deve garantir:

- hashing seguro de senhas;
- autenticação nos endpoints protegidos;
- autorização por usuário;
- expiração do JWT;
- ausência de dados financeiros em endpoints públicos;
- validação no backend independentemente do frontend.

O isolamento entre usuários é uma regra de negócio crítica e deve possuir cobertura automatizada.

## API

A API deve:

- utilizar os HTTP verbs adequados;
- retornar códigos HTTP apropriados;
- retornar JSON;
- possuir respostas padronizadas;
- possuir estrutura padronizada para erros;
- validar todos os dados recebidos;
- utilizar Resources do Laravel para padronização das respostas.

Os endpoints devem ser documentados como parte da implementação.

## Experiência de uso

O frontend deve possuir, quando aplicável:

- estado de carregamento;
- estado de erro;
- estado vazio;
- feedback de sucesso;
- feedback de falha;
- tratamento de sessão expirada;
- tratamento dos erros provenientes da API.

A aplicação deve funcionar adequadamente em:

- desktop;
- tablet;
- dispositivos móveis.

Também deve seguir boas práticas de acessibilidade, incluindo:

- contraste adequado;
- labels associados aos campos;
- navegação por teclado;
- feedback textual para erros;
- elementos semanticamente apropriados.

## Testes

O backend deve possuir testes automatizados, incluindo obrigatoriamente:

### Testes unitários

Devem validar regras isoladas, como:

- cálculo de saldo;
- cálculo de percentual de despesas;
- cálculo de parcelas;
- cálculo de dias úteis;
- cálculo de datas recorrentes.

### Testes de feature

Devem validar fluxos completos, como:

- cadastro;
- login;
- logout;
- criação de categoria;
- criação de transação;
- criação de recorrência;
- criação de parcelamento;
- consulta do dashboard.

### Testes de API

Devem verificar:

- códigos HTTP;
- estrutura das respostas;
- payloads;
- validações;
- autenticação;
- autorização;
- isolamento entre usuários.

Toda nova regra de negócio deve possuir testes automatizados correspondentes.

## Princípio de evolução

Esta documentação representa o comportamento esperado do sistema.

Alterações nas regras de negócio devem primeiro ser refletidas na documentação e somente depois implementadas no código.

Nenhuma implementação deve introduzir comportamento de negócio que não esteja especificado sem que essa decisão seja documentada.

Para detalhes operacionais destinados ao agente de desenvolvimento, consulte `AGENTS.md`. Para a sequência de implementação e critérios de aceite, consulte `TASKS.md`.
