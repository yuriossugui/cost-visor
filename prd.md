# Sistema de Gestão Financeira Pessoal - Cost Visor

## 1. Objetivo

Criar um web app de uso pessoal para controle de gastos e gestão financeira pessoal.

O sistema deverá permitir ao usuário registrar, organizar e acompanhar receitas e despesas, incluindo transações recorrentes e parceladas, além de fornecer uma visão consolidada de sua situação financeira por meio de indicadores, listagens e gráficos.

---

# 2. Escopo

O sistema deverá permitir:

* criação e gerenciamento de usuários;
* autenticação;
* gerenciamento de categorias;
* gerenciamento de receitas;
* gerenciamento de despesas;
* gerenciamento de transações recorrentes;
* gerenciamento de transações parceladas;
* consulta do histórico financeiro;
* consulta das transações por mês;
* visualização de indicadores financeiros;
* visualização da concentração de despesas por categoria.

O sistema trabalhará inicialmente com **saldo global**, sem necessidade de cadastro ou controle de múltiplas contas financeiras.

---

# 3. Fora do escopo

A primeira versão do sistema não deverá implementar:

* integração bancária;
* Open Finance;
* importação automática de extratos;
* sincronização com cartões de crédito;
* investimentos;
* controle de patrimônio;
* múltiplas moedas;
* pagamentos;
* compartilhamento de dados financeiros entre usuários;
* múltiplas contas financeiras;
* integração com serviços financeiros externos.

Esses recursos poderão ser considerados futuramente, mas não fazem parte da primeira versão.

---

# 4. Arquitetura

O projeto deverá obrigatoriamente possuir dois diretórios raiz:

```text
/backend
/frontend
```

## 4.1 Backend

O diretório `/backend` deverá conter uma aplicação Laravel responsável exclusivamente pela API REST.

O backend será responsável por:

* autenticação;
* autorização;
* validação dos dados;
* regras de negócio;
* persistência dos dados;
* processamento de recorrências;
* processamento de parcelamentos;
* cálculo dos dados financeiros;
* fornecimento dos dados consumidos pelo frontend.

O backend não deverá possuir páginas ou interfaces HTML destinadas ao usuário final.

## 4.2 Frontend

O diretório `/frontend` deverá conter uma aplicação React + TypeScript.

O frontend será responsável por:

* interface gráfica;
* navegação;
* comunicação com a API;
* apresentação dos dados;
* gerenciamento do estado da aplicação;
* feedback visual das operações;
* validações relacionadas à experiência do usuário.

---

# 5. Stack

## 5.1 Backend

* PHP
* Laravel
* API REST
* JWT
* banco de dados relacional

## 5.2 Frontend

* React
* TypeScript
* shadcn/ui

A interface deverá possuir:

* visual moderno;
* visual minimalista;
* layout responsivo;
* boa legibilidade;
* contraste adequado;
* cor principal escura;
* elementos de contraste para facilitar identificação das informações.

## 5.3 Banco de dados

* SQLite

---

# 6. Glossário

## Usuário

Pessoa que possui uma conta no sistema.

## Categoria

Classificação utilizada para organizar as transações financeiras.

Uma categoria possui um tipo:

* receita;
* despesa.

## Transação

Registro financeiro individual que representa uma receita ou despesa.

## Recorrência

Regra utilizada para gerar transações automaticamente de acordo com uma frequência definida.

## Parcelamento

Uma despesa dividida em múltiplas parcelas.

Cada parcela será registrada individualmente como uma transação e será contabilizada no mês correspondente à sua data.

## Saldo

Resultado da diferença entre todas as receitas e todas as despesas do período:

```text
saldo = receitas - despesas
```

O sistema utilizará inicialmente um saldo financeiro global.

---

# 7. Requisitos funcionais

## RF01 — Cadastro de usuário

O sistema deverá permitir que um novo usuário crie uma conta.

O cadastro deverá possuir:

* nome;
* e-mail;
* senha;
* confirmação de senha.

### Regras

* o nome é obrigatório;
* o e-mail é obrigatório;
* o e-mail deve possuir formato válido;
* o e-mail deve ser único;
* a senha é obrigatória;
* a senha deverá atender aos requisitos mínimos de segurança;
* a senha nunca poderá ser armazenada em texto puro.

---

## RF02 — Login

O sistema deverá permitir que o usuário realize login utilizando:

* e-mail;
* senha.

Após uma autenticação bem-sucedida, a API deverá emitir um token JWT.

O frontend deverá utilizar o token para acessar os endpoints protegidos.

---

## RF03 — Logout

O sistema deverá permitir que o usuário encerre sua sessão.

O frontend deverá remover o token de autenticação armazenado localmente.

O mecanismo de invalidação/revogação de JWT deverá ser definido na implementação do backend.

---

## RF04 — Perfil do usuário

O usuário deverá poder consultar e atualizar seus dados pessoais básicos.

Dados inicialmente suportados:

* nome;
* e-mail.

A alteração do e-mail deverá respeitar a regra de unicidade.

---

## RF05 — Gestão de categorias

O usuário deverá poder:

* criar categorias;
* listar categorias;
* consultar uma categoria;
* atualizar categorias;
* excluir categorias.

Uma categoria deverá possuir:

* nome;
* cor;
* tipo.

O tipo deverá aceitar apenas:

```text
RECEITA
DESPESA
```

### Regras

* o nome é obrigatório;
* a cor é obrigatória;
* a cor deverá possuir formato válido;
* o tipo é obrigatório;
* uma categoria pertence a um único usuário;
* categorias de outros usuários não poderão ser acessadas;
* uma categoria utilizada por transações não poderá ser excluída.

Caso o usuário tente excluir uma categoria que possui transações associadas, a API deverá rejeitar a operação e informar o motivo.

---

## RF06 — Gestão de transações

O usuário deverá poder:

* criar transações;
* listar transações;
* consultar uma transação;
* atualizar transações;
* excluir transações.

Uma transação deverá possuir:

* valor;
* descrição;
* data;
* tipo;
* categoria.

O tipo deverá aceitar:

```text
RECEITA
DESPESA
```

### Regras

* o valor é obrigatório;
* o valor deve ser maior que zero;
* a descrição é obrigatória;
* a data é obrigatória;
* o tipo é obrigatório;
* a categoria é obrigatória;
* a categoria deverá pertencer ao usuário autenticado;
* o tipo da categoria deverá ser compatível com o tipo da transação.

Exemplo:

```text
Transação: DESPESA
Categoria: Alimentação (DESPESA)
```

é válida.

Enquanto:

```text
Transação: DESPESA
Categoria: Salário (RECEITA)
```

é inválida.

---

# 8. Recorrências

## RF07 — Gestão de transações recorrentes

O usuário deverá poder configurar transações recorrentes.

Uma recorrência deverá possuir:

* valor;
* descrição;
* tipo;
* categoria;
* frequência;
* data inicial;
* data final opcional.

As frequências inicialmente suportadas deverão ser:

* diária;
* semanal;
* mensal;
* anual.

O sistema deverá gerar as transações correspondentes à recorrência.

---

## RF08 — Recorrência por dia útil

O sistema deverá permitir recorrências baseadas em dia útil.

Para este projeto, considera-se dia útil:

> qualquer dia que não seja domingo ou feriado.

O sábado será considerado dia útil conforme essa definição.

Exemplo:

```text
Regra:
5º dia útil de cada mês

Janeiro:
01 → dia útil
02 → dia útil
03 → dia útil
04 → dia útil
05 → dia útil
```

Caso uma data seja domingo ou feriado, ela não deverá ser contabilizada como dia útil.

O cálculo deverá considerar o calendário de feriados definido pela aplicação.

A estratégia para manutenção do calendário de feriados deverá ser definida na implementação.

---

# 9. Parcelamentos

## RF09 — Criação de transação parcelada

O usuário deverá poder registrar uma despesa parcelada.

O cadastro deverá permitir informar:

* valor total;
* quantidade de parcelas;
* data da primeira parcela;
* descrição;
* categoria.

Exemplo:

```text
Compra:
R$ 900,00

Parcelamento:
3 parcelas

Resultado:

01/10 → R$ 300,00
01/11 → R$ 300,00
01/12 → R$ 300,00
```

Cada parcela deverá ser registrada como uma transação individual.

As parcelas deverão ser contabilizadas no mês correspondente à sua data.

No exemplo acima:

```text
Outubro → R$ 300,00
Novembro → R$ 300,00
Dezembro → R$ 300,00
```

O sistema deverá manter a relação lógica entre as parcelas pertencentes ao mesmo parcelamento, permitindo identificar que elas fazem parte da mesma compra.

Essa relação deverá ser implementada sem alterar o fato de que cada parcela representa uma transação individual.

---

# 10. Consulta de transações

## RF10 — Listagem de transações

O sistema deverá apresentar uma listagem das transações do usuário.

A listagem deverá permitir:

* filtro por mês;
* filtro por período;
* filtro por tipo;
* filtro por categoria;
* busca por descrição;
* ordenação por data;
* paginação.

A ordenação padrão deverá ser:

```text
data mais recente → data mais antiga
```

A listagem deverá diferenciar visualmente receitas e despesas.

---

# 11. Dashboard

## RF11 — Painel de gestão financeira

O sistema deverá possuir uma tela específica para acompanhamento financeiro.

Por padrão, o dashboard deverá apresentar os dados do **mês corrente**.

O usuário deverá poder selecionar outro mês para análise.

Exemplo:

```text
Setembro/2026
Agosto/2026
Julho/2026
...
```

O período selecionado deverá ser aplicado aos indicadores, gráficos e listagens do dashboard.

---

## RF12 — Saldo do período

O dashboard deverá apresentar o saldo do período selecionado.

A fórmula será:

```text
saldo = total de receitas - total de despesas
```

Exemplo:

```text
Receitas:  R$ 5.000,00
Despesas:  R$ 3.200,00

Saldo:
R$ 1.800,00
```

---

## RF13 — Total de receitas

O dashboard deverá apresentar o total de receitas do período selecionado.

O valor deverá ser calculado pela soma de todas as transações do tipo `RECEITA` pertencentes ao período.

---

## RF14 — Total de despesas

O dashboard deverá apresentar o total de despesas do período selecionado.

O valor deverá ser calculado pela soma de todas as transações do tipo `DESPESA` pertencentes ao período.

---

## RF15 — Quantidade de transações

O dashboard deverá apresentar a quantidade total de transações existentes no período selecionado.

---

## RF16 — Transações do período

O dashboard deverá apresentar uma listagem das transações pertencentes ao período selecionado.

A listagem deverá:

* apresentar receitas e despesas;
* apresentar descrição;
* apresentar categoria;
* apresentar valor;
* apresentar data;
* ordenar as transações por data.

---

## RF17 — Concentração de despesas

O dashboard deverá apresentar um gráfico em formato de pizza representando a concentração das despesas por categoria.

A regra de cálculo será:

```text
percentual =
(soma das despesas da categoria / soma total das despesas) × 100
```

Exemplo:

```text
Alimentação: R$ 500
Transporte:  R$ 300
Moradia:     R$ 1.200

Total:
R$ 2.000

Alimentação:
25%

Transporte:
15%

Moradia:
60%
```

A soma dos percentuais deverá representar 100%, considerando eventuais arredondamentos.

Caso o total de despesas seja igual a zero, o sistema não deverá realizar uma divisão por zero.

Nesse cenário, o frontend deverá apresentar um estado vazio para o gráfico.

---

# 12. Regras de negócio

## RB01 — Isolamento de dados

Todos os dados financeiros deverão pertencer a um usuário.

Um usuário somente poderá:

* consultar;
* criar;
* atualizar;
* excluir

dados pertencentes à própria conta.

O conhecimento do identificador de um recurso de outro usuário não poderá permitir seu acesso.

Essa regra deverá ser obrigatoriamente coberta por testes automatizados.

---

## RB02 — Compatibilidade entre categoria e transação

Uma transação deverá possuir o mesmo tipo da categoria utilizada.

```text
RECEITA → categoria RECEITA
DESPESA → categoria DESPESA
```

---

## RB03 — Categorias utilizadas

Uma categoria que possua transações associadas não poderá ser excluída.

---

## RB04 — Parcelas

Cada parcela deverá ser uma transação individual.

A parcela deverá ser contabilizada exclusivamente no período correspondente à sua data.

---

## RB05 — Recorrências

Uma recorrência deverá gerar transações de acordo com sua configuração.

As transações geradas deverão respeitar:

* frequência;
* data inicial;
* data final, quando definida;
* categoria;
* tipo;
* valor.

---

## RB06 — Dia útil

Para recorrências que utilizem dia útil:

* domingos não são dias úteis;
* feriados não são dias úteis;
* sábados são considerados dias úteis;
* a aplicação deverá utilizar o calendário de feriados configurado.

---

## RB07 — Saldo global

O sistema deverá possuir um único saldo financeiro global para o usuário.

Não haverá, na primeira versão, separação de saldo por:

* banco;
* carteira;
* conta corrente;
* cartão;
* instituição financeira.

---

# 13. Requisitos não funcionais

## RNF01 — Segurança

* senhas deverão utilizar hashing seguro;
* endpoints protegidos deverão exigir autenticação;
* recursos deverão ser protegidos contra acesso de outros usuários;
* JWT deverá possuir expiração;
* dados financeiros não deverão ser disponibilizados em endpoints públicos;
* validações deverão existir no backend independentemente das validações do frontend.

---

## RNF02 — API REST

A API deverá:

* utilizar HTTP verbs adequadamente;
* utilizar códigos HTTP apropriados;
* retornar JSON;
* possuir respostas padronizadas;
* possuir estrutura padronizada para erros;
* validar todos os dados recebidos;
* utilizar Resources do Laravel para padronização das respostas.

---

## RNF03 — Frontend

O frontend deverá possuir:

* estado de carregamento;
* estado de erro;
* estado vazio;
* feedback de sucesso;
* feedback de falha;
* tratamento de sessão expirada;
* tratamento de erros provenientes da API.

---

## RNF04 — Responsividade

A aplicação deverá funcionar adequadamente em:

* desktop;
* tablet;
* dispositivos móveis.

---

## RNF05 — Acessibilidade

A interface deverá seguir boas práticas de acessibilidade, incluindo:

* contraste adequado;
* labels associados aos campos;
* navegação por teclado;
* feedback textual para erros;
* elementos semanticamente apropriados.

---

## RNF06 — Manutenibilidade

A aplicação deverá possuir separação clara de responsabilidades.

Regras de negócio não deverão ficar diretamente acopladas aos componentes visuais do frontend ou aos controllers da API.

O código deverá priorizar:

* baixo acoplamento;
* alta coesão;
* reutilização;
* testabilidade;
* separação de responsabilidades.

---

# 14. Estratégia de testes

O backend deverá possuir testes automatizados.

## Testes unitários

Deverão validar regras de negócio isoladas.

Exemplos:

* cálculo de saldo;
* cálculo de percentual de despesas;
* cálculo de parcelas;
* cálculo de dias úteis;
* cálculo de datas recorrentes.

## Testes de feature

Deverão validar fluxos completos.

Exemplos:

* cadastro de usuário;
* login;
* logout;
* criação de categoria;
* criação de transação;
* criação de recorrência;
* criação de parcelamento;
* consulta do dashboard.

## Testes de API

Deverão validar:

* códigos HTTP;
* estrutura das respostas;
* payloads;
* validações;
* autenticação;
* autorização;
* isolamento entre usuários.

## Testes de regras financeiras

Deverão cobrir especialmente:

* cálculo do saldo;
* receitas;
* despesas;
* parcelamentos;
* recorrências;
* dias úteis;
* feriados;
* divisão das despesas por categoria;
* período mensal;
* ausência de despesas.

## Regra geral

Toda nova regra de negócio deverá possuir testes automatizados correspondentes.

---

# 15. Critérios gerais de aceite

Uma funcionalidade será considerada implementada somente quando:

* possuir implementação no backend;
* possuir validação adequada;
* possuir autorização adequada;
* possuir testes automatizados quando aplicável;
* possuir endpoint documentado;
* possuir integração com o frontend quando fizer parte da interface;
* possuir tratamento de loading, erro e estado vazio no frontend quando aplicável;
* respeitar as regras de negócio definidas neste documento.

---

# 16. Critérios de aceite — autenticação

### Cadastro

* [ ] usuário consegue criar uma conta;
* [ ] e-mail duplicado é rejeitado;
* [ ] e-mail inválido é rejeitado;
* [ ] senha inválida é rejeitada;
* [ ] senha não é armazenada em texto puro;
* [ ] cadastro pertence ao novo usuário.

### Login

* [ ] usuário consegue realizar login;
* [ ] credenciais inválidas são rejeitadas;
* [ ] login válido retorna JWT;
* [ ] endpoints protegidos exigem autenticação.

### Autorização

* [ ] usuário consegue acessar seus próprios recursos;
* [ ] usuário não consegue acessar recursos de outro usuário;
* [ ] usuário não consegue alterar recursos de outro usuário;
* [ ] usuário não consegue excluir recursos de outro usuário.

---

# 17. Critérios de aceite — categorias

* [ ] usuário consegue criar categoria;
* [ ] usuário consegue listar suas categorias;
* [ ] usuário consegue consultar categoria;
* [ ] usuário consegue atualizar categoria;
* [ ] usuário consegue excluir categoria sem transações;
* [ ] categoria utilizada por transações não pode ser excluída;
* [ ] categorias de outros usuários não podem ser acessadas;
* [ ] categoria possui tipo válido;
* [ ] categoria possui nome obrigatório;
* [ ] categoria possui cor válida.

---

# 18. Critérios de aceite — transações

* [ ] usuário autenticado consegue criar transação;
* [ ] usuário não autenticado não consegue criar transação;
* [ ] valor deve ser maior que zero;
* [ ] descrição é obrigatória;
* [ ] data é obrigatória;
* [ ] categoria é obrigatória;
* [ ] categoria deve pertencer ao usuário;
* [ ] tipo da categoria deve ser compatível com o tipo da transação;
* [ ] usuário consegue editar sua transação;
* [ ] usuário consegue excluir sua transação;
* [ ] usuário não consegue editar transação de outro usuário;
* [ ] usuário não consegue excluir transação de outro usuário.

---

# 19. Critérios de aceite — parcelamento

* [ ] usuário consegue criar uma despesa parcelada;
* [ ] quantidade de parcelas deve ser válida;
* [ ] cada parcela é registrada individualmente;
* [ ] cada parcela possui sua própria data;
* [ ] cada parcela é contabilizada no mês correspondente;
* [ ] parcelas possuem relação com o parcelamento original;
* [ ] o valor total das parcelas corresponde ao valor total da compra, respeitando eventuais regras de arredondamento;
* [ ] parcelas de um usuário não podem ser acessadas por outro usuário.

---

# 20. Critérios de aceite — recorrência

* [ ] usuário consegue criar uma recorrência;
* [ ] recorrência possui frequência válida;
* [ ] recorrência possui data inicial;
* [ ] recorrência pode possuir data final;
* [ ] transações são geradas conforme a frequência;
* [ ] recorrências pertencem ao usuário;
* [ ] recorrências de outros usuários não podem ser acessadas;
* [ ] recorrências baseadas em dia útil respeitam feriados e domingos;
* [ ] sábado é considerado dia útil.

---

# 21. Critérios de aceite — dashboard

* [ ] dashboard apresenta o mês corrente por padrão;
* [ ] usuário consegue selecionar outro mês;
* [ ] indicadores respeitam o mês selecionado;
* [ ] saldo é calculado como receitas menos despesas;
* [ ] total de receitas considera somente receitas do período;
* [ ] total de despesas considera somente despesas do período;
* [ ] transações exibidas pertencem ao período selecionado;
* [ ] gráfico agrupa despesas por categoria;
* [ ] percentual de cada categoria é calculado corretamente;
* [ ] ausência de despesas apresenta estado vazio;
* [ ] nenhum cálculo realiza divisão por zero.

---

# 22. Decisões de domínio

As seguintes decisões fazem parte da especificação inicial:

| Decisão                   | Definição                                       |
| ------------------------- | ----------------------------------------------- |
| Saldo                     | Global                                          |
| Contas financeiras        | Não existem na primeira versão                  |
| Categorias                | Pertencem a um usuário                          |
| Categoria utilizada       | Não pode ser excluída                           |
| Tipo da transação         | Receita ou despesa                              |
| Tipo da categoria         | Receita ou despesa                              |
| Categoria/transação       | Os tipos devem ser compatíveis                  |
| Parcelamento              | Cada parcela é uma transação individual         |
| Contabilização de parcela | No mês correspondente à data da parcela         |
| Dashboard                 | Mês corrente por padrão                         |
| Dashboard histórico       | Permite selecionar outro mês                    |
| Dia útil                  | Dia que não seja domingo ou feriado             |
| Sábado                    | Considerado dia útil                            |
| Isolamento                | Cada usuário acessa somente seus próprios dados |
| Autenticação              | JWT                                             |
| Backend                   | Laravel REST API                                |
| Frontend                  | React + TypeScript                              |
| UI                        | shadcn/ui                                       |

---

# 23. Princípio de evolução

A implementação deverá respeitar esta documentação como fonte de verdade do comportamento esperado do sistema.

Alterações nas regras de negócio deverão primeiro ser refletidas na documentação e somente depois implementadas no código.

Nenhuma implementação deverá introduzir comportamento de negócio não especificado sem que a decisão seja documentada.
