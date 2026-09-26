# TASKS.md — Cost Visor

> Backlog de implementação baseado no `AGENTS.md` e na especificação funcional.
>
> **Status permitidos:** `pending`, `in progress`, `completed`, `blocked`.
>
> Uma tarefa só pode ser marcada como `completed` quando seus critérios de aceite forem atendidos e os testes obrigatórios tiverem sido executados com sucesso.

## 0. Regras de execução

- [ ] Toda tarefa de backend que introduza regra de negócio deve possuir teste unitário quando houver lógica isolável.
- [ ] Toda funcionalidade exposta por fluxo da aplicação deve possuir teste de feature.
- [ ] Endpoints devem possuir cobertura de autenticação, autorização, validação e resposta quando aplicável.
- [ ] O isolamento entre usuários é obrigatório.
- [ ] Não implementar comportamento fora da especificação.
- [ ] Se uma decisão funcional não estiver definida, marcar a tarefa como `blocked` até documentar a decisão.
- [ ] Atualizar o status da tarefa somente após verificar todos os critérios.
- [ ] Executar a suíte relevante e registrar o resultado no histórico/commit conforme o fluxo do projeto.

---

## Fase 1 — Fundação

### T01 — Estrutura inicial do projeto
**Status:** `completed`

**Objetivo:** estabelecer a separação `/backend` e `/frontend` e preparar as bases tecnológicas definidas.

**Escopo:**
- criar/preparar Laravel no backend;
- criar/preparar React + TypeScript no frontend;
- configurar SQLite;
- configurar shadcn/ui no frontend;
- preparar a comunicação entre frontend e API;
- manter backend exclusivamente como API.

**Testes obrigatórios:**
- teste/health check da API;
- validação de que o frontend consegue consumir a API base.

**Aceitação:**
- [x] `/backend` existe e é Laravel;
- [x] `/frontend` existe e é React + TypeScript;
- [x] SQLite está configurado;
- [x] shadcn/ui está disponível;
- [x] backend não possui páginas HTML de usuário;
- [x] aplicação inicializa sem erros;
- [x] testes existentes passam.

**Dependências:** nenhuma.

---

### T02 — Base de testes e convenções
**Status:** `completed`

**Objetivo:** estabelecer a infraestrutura necessária para testes unitários e de feature.

**Escopo:**
- configurar execução dos testes do backend;
- preparar banco de testes;
- estabelecer estrutura para testes unitários;
- estabelecer estrutura para testes de feature/API.

**Testes obrigatórios:**
- pelo menos um teste unitário;
- pelo menos um teste de feature;
- execução completa da suíte inicial.

**Aceitação:**
- [x] testes unitários podem ser executados;
- [x] testes de feature podem ser executados;
- [x] banco de testes funciona de forma isolada;
- [x] existe evidência de execução bem-sucedida da suíte.

**Dependências:** T01.

---

## Fase 2 — Usuários e autenticação

### T03 — Modelo e cadastro de usuário
**Status:** `completed`

**Escopo:**
- criar persistência de usuário;
- implementar cadastro com nome, e-mail, senha e confirmação;
- validar e-mail;
- garantir unicidade;
- aplicar hashing seguro;
- validar requisitos mínimos de senha.

**Testes unitários:**
- validações de senha quando houver lógica isolável.

**Testes de feature:**
- cadastro válido;
- e-mail duplicado;
- e-mail inválido;
- senha inválida;
- confirmação de senha inválida;
- senha não armazenada em texto puro.

**Aceitação:**
- [x] cadastro válido funciona;
- [x] e-mail duplicado é rejeitado;
- [x] e-mail inválido é rejeitado;
- [x] senha inválida é rejeitada;
- [x] senha nunca é persistida em texto puro;
- [x] testes unitários aplicáveis passam;
- [x] testes de feature passam.

**Dependências:** T02.

---

### T04 — Login com JWT
**Status:** `completed`

**Escopo:**
- implementar login por e-mail e senha;
- emitir JWT em autenticação bem-sucedida;
- proteger endpoints com autenticação;
- configurar expiração do JWT.

**Decisão técnica:** o mecanismo específico de JWT deve ser definido durante a implementação sem alterar o comportamento funcional.

**Testes de feature:**
- login válido;
- credenciais inválidas;
- acesso sem token;
- acesso com token válido;
- token expirado.

**Aceitação:**
- [x] login válido retorna JWT;
- [x] credenciais inválidas são rejeitadas;
- [x] endpoint protegido rejeita requisição não autenticada;
- [x] token válido permite acesso;
- [x] JWT possui expiração;
- [x] testes passam.

**Dependências:** T03.

---

### T05 — Logout e perfil
**Status:** `completed`

**Escopo:**
- implementar logout;
- remover token no frontend;
- definir mecanismo de invalidação/revogação no backend;
- consultar perfil;
- atualizar nome e e-mail;
- manter unicidade do e-mail.

**Testes de feature:**
- consulta do perfil;
- atualização do nome;
- atualização do e-mail;
- rejeição de e-mail duplicado;
- logout;
- comportamento após logout conforme mecanismo definido.

**Aceitação:**
- [x] usuário consulta seu perfil;
- [x] usuário atualiza nome;
- [x] usuário atualiza e-mail;
- [x] e-mail duplicado é rejeitado;
- [x] logout encerra a sessão conforme mecanismo implementado;
- [x] frontend remove o token;
- [x] testes passam.

**Dependências:** T04.

---

## Fase 3 — Categorias

### T06 — Modelo e CRUD de categorias
**Status:** `completed`

**Escopo:**
- persistir categorias;
- implementar criar/listar/consultar/atualizar/excluir;
- campos nome, cor e tipo;
- tipos `RECEITA` e `DESPESA`;
- vincular categoria ao usuário.

**Testes unitários:**
- validações de domínio aplicáveis.

**Testes de feature/API:**
- CRUD completo;
- tipo inválido;
- nome ausente;
- cor inválida;
- acesso sem autenticação;
- isolamento entre usuários.

**Aceitação:**
- [x] CRUD funciona;
- [x] tipo aceita somente `RECEITA` ou `DESPESA`;
- [x] nome é obrigatório;
- [x] cor é obrigatória e válida;
- [x] categoria pertence ao usuário autenticado;
- [x] outro usuário não acessa a categoria;
- [x] testes unitários aplicáveis passam;
- [x] testes de feature/API passam.

**Dependências:** T04.

---

### T07 — Proteção contra exclusão de categoria utilizada
**Status:** `completed`

**Escopo:**
- impedir exclusão de categoria com transações associadas;
- retornar erro padronizado explicando o motivo.

**Testes de feature:**
- exclusão de categoria sem transações;
- rejeição de exclusão com transações;
- tentativa cross-user.

**Aceitação:**
- [x] categoria sem transações pode ser excluída;
- [x] categoria utilizada não pode ser excluída;
- [x] API informa o motivo;
- [x] isolamento continua garantido;
- [x] testes passam.

**Dependências:** T06, T09.

---

## Fase 4 — Transações

### T08 — Modelo e CRUD de transações
**Status:** `completed`

**Escopo:**
- persistir transações;
- implementar criar/listar/consultar/atualizar/excluir;
- campos valor, descrição, data, tipo e categoria;
- vincular ao usuário.

**Testes unitários:**
- validação de valor maior que zero;
- compatibilidade de tipos, caso isolável.

**Testes de feature/API:**
- CRUD;
- valor zero/negativo;
- campos obrigatórios;
- categoria inexistente;
- categoria de outro usuário;
- transação de outro usuário;
- incompatibilidade entre tipo e categoria.

**Aceitação:**
- [x] valor é maior que zero;
- [x] descrição é obrigatória;
- [x] data é obrigatória;
- [x] tipo é obrigatório;
- [x] categoria é obrigatória;
- [x] categoria pertence ao usuário;
- [x] tipos são compatíveis;
- [x] CRUD funciona;
- [x] isolamento é garantido;
- [x] testes passam.

**Dependências:** T06.

---

### T09 — Regra de compatibilidade categoria/transação
**Status:** `completed`

**Escopo:**
- garantir que `RECEITA` use categoria `RECEITA`;
- garantir que `DESPESA` use categoria `DESPESA`.

**Testes unitários:**
- combinações válidas;
- combinações inválidas.

**Testes de feature:**
- criação válida;
- criação inválida;
- atualização inválida.

**Aceitação:**
- [x] receita com categoria de receita é aceita;
- [x] despesa com categoria de despesa é aceita;
- [x] receita com categoria de despesa é rejeitada;
- [x] despesa com categoria de receita é rejeitada;
- [x] atualização respeita a mesma regra;
- [x] testes passam.

**Dependências:** T06, T08.

---

### T10 — Listagem e filtros de transações
**Status:** `completed`

**Escopo:**
- filtro por mês;
- filtro por período;
- filtro por tipo;
- filtro por categoria;
- busca por descrição;
- ordenação por data;
- paginação.

**Testes unitários:**
- lógica de filtros/consulta quando houver componente isolável.

**Testes de feature/API:**
- cada filtro;
- combinação de filtros;
- paginação;
- ordenação padrão;
- isolamento por usuário.

**Aceitação:**
- [x] filtros funcionam;
- [x] paginação funciona;
- [x] ordenação padrão é da mais recente para a mais antiga;
- [x] resultados pertencem somente ao usuário autenticado;
- [x] testes passam.

**Dependências:** T08, T09.

---

## Fase 5 — Parcelamentos

### T11 — Regra de cálculo e criação de parcelamento
**Status:** `completed`

**Escopo:**
- receber valor total;
- receber quantidade de parcelas;
- receber data da primeira parcela;
- receber descrição;
- receber categoria;
- gerar parcelas;
- manter relação lógica entre parcelas e parcelamento;
- registrar cada parcela como transação individual.

**Testes unitários obrigatórios:**
- cálculo da quantidade de parcelas;
- distribuição do valor;
- arredondamento quando aplicável;
- cálculo das datas.

**Testes de feature obrigatórios:**
- criação de parcelamento;
- persistência das parcelas;
- relação com origem;
- acesso cross-user.

**Aceitação:**
- [x] quantidade de parcelas é válida;
- [x] cada parcela é uma transação;
- [x] cada parcela possui sua data;
- [x] relação lógica com o parcelamento é mantida;
- [x] soma das parcelas corresponde ao total, respeitando arredondamento;
- [x] parcelamento pertence ao usuário;
- [x] outro usuário não acessa parcelas;
- [x] testes unitários passam;
- [x] testes de feature passam.

**Dependências:** T08, T09.

---

### T12 — Contabilização mensal das parcelas
**Status:** `completed`

**Escopo:**
- garantir que cada parcela seja contabilizada exclusivamente no mês correspondente à sua data.

**Testes unitários obrigatórios:**
- parcelamento atravessando meses;
- datas dentro do mesmo mês;
- consulta por período.

**Testes de feature:**
- dashboard/listagem mensal com parcelas.

**Aceitação:**
- [x] parcela aparece somente no período da própria data;
- [x] parcelamento que atravessa meses é distribuído entre os meses corretos;
- [x] soma mensal respeita cada parcela individual;
- [x] testes passam.

**Dependências:** T11, T10.

---

## Fase 6 — Recorrências

### T13 — Modelo e CRUD de recorrências
**Status:** `completed`

**Escopo:**
- persistir recorrências;
- campos valor, descrição, tipo, categoria, frequência, data inicial e data final opcional;
- frequências diária, semanal, mensal e anual;
- vincular ao usuário.

**Testes unitários:**
- validações de frequência e datas quando isoláveis.

**Testes de feature/API:**
- CRUD;
- frequência inválida;
- data inicial;
- data final;
- isolamento.

**Aceitação:**
- [x] recorrência pode ser criada;
- [x] frequência aceita somente as opções especificadas;
- [x] data inicial é obrigatória;
- [x] data final pode ser opcional;
- [x] categoria pertence ao usuário;
- [x] isolamento é garantido;
- [x] testes passam.

**Dependências:** T09.

---

### T14 — Geração de transações recorrentes
**Status:** `completed`

**Escopo:**
- gerar transações conforme frequência;
- respeitar data inicial;
- respeitar data final quando definida;
- preservar valor, tipo e categoria.

**Testes unitários obrigatórios:**
- recorrência diária;
- semanal;
- mensal;
- anual;
- limite por data inicial/final.

**Testes de feature obrigatórios:**
- criação de recorrência e geração das transações;
- encerramento pela data final;
- isolamento.

**Aceitação:**
- [x] frequência diária funciona;
- [x] semanal funciona;
- [x] mensal funciona;
- [x] anual funciona;
- [x] data inicial é respeitada;
- [x] data final é respeitada;
- [x] dados da transação gerada correspondem à recorrência;
- [x] testes passam.

**Dependências:** T13.

---

### T15 — Calendário e regra de dia útil
**Status:** `completed`

**Escopo:**
- implementar cálculo de dia útil;
- considerar domingo como não útil;
- considerar feriado como não útil;
- considerar sábado como útil;
- implementar estratégia para calendário de feriados;
- documentar a decisão técnica sobre manutenção dos feriados.

**Testes unitários obrigatórios:**
- sábado;
- domingo;
- dia comum;
- feriado;
- sequência de dias úteis;
- cálculo de N-ésimo dia útil.

**Testes de feature:**
- recorrência baseada em dia útil;
- recorrência em mês com feriado;
- comportamento em domingo;
- comportamento em sábado.

**Aceitação:**
- [x] sábado é considerado útil;
- [x] domingo não é considerado útil;
- [x] feriado não é considerado útil;
- [x] calendário configurado é utilizado;
- [x] cálculo do dia útil é determinístico;
- [x] testes unitários passam;
- [x] testes de feature passam.

**Dependências:** T14.

---

### T16 — Recorrência baseada em dia útil
**Status:** `completed`

**Escopo:**
- integrar a regra de dia útil ao processamento de recorrências.

**Testes unitários obrigatórios:**
- data escolhida quando o dia de referência é domingo;
- data escolhida quando há feriado;
- sábado contado como dia útil.

**Testes de feature obrigatórios:**
- recorrência por N-ésimo dia útil;
- geração em mês com feriado.

**Aceitação:**
- [x] recorrência respeita domingos;
- [x] respeita feriados;
- [x] conta sábados;
- [x] utiliza calendário configurado;
- [x] testes passam.

**Dependências:** T15.

---

## Fase 7 — Dashboard e cálculos financeiros

### T17 — Serviços de cálculo financeiro
**Status:** `completed`

**Escopo:**
- cálculo de saldo;
- total de receitas;
- total de despesas;
- quantidade de transações;
- concentração de despesas por categoria.

**Testes unitários obrigatórios:**
- saldo;
- receitas;
- despesas;
- quantidade;
- percentuais;
- arredondamento;
- ausência de despesas.

**Aceitação:**
- [x] saldo = receitas - despesas;
- [x] receitas consideram somente `RECEITA`;
- [x] despesas consideram somente `DESPESA`;
- [x] quantidade considera transações do período;
- [x] percentual por categoria é calculado corretamente;
- [x] não existe divisão por zero;
- [x] testes unitários passam.

**Dependências:** T08, T12, T14.

---

### T18 — API do dashboard
**Status:** `completed`

**Escopo:**
- endpoint(s) necessários para fornecer dados do dashboard;
- mês corrente como padrão;
- seleção de outro mês;
- indicadores;
- transações do período;
- concentração por categoria.

**Testes de feature/API obrigatórios:**
- mês corrente;
- mês selecionado;
- saldo;
- receitas;
- despesas;
- quantidade;
- transações;
- concentração;
- ausência de despesas;
- isolamento entre usuários.

**Aceitação:**
- [x] mês corrente é padrão;
- [x] outro mês pode ser selecionado;
- [x] todos os indicadores respeitam o período;
- [x] listagem respeita o período;
- [x] gráfico recebe dados agrupados por categoria;
- [x] zero despesas não causa erro;
- [x] isolamento é garantido;
- [x] testes passam.

**Dependências:** T17, T10.

---

## Fase 8 — Frontend

### T19 — Estrutura visual e navegação
**Status:** `completed`

**Escopo:**
- estrutura da aplicação React;
- navegação;
- layout responsivo;
- componentes base com shadcn/ui;
- identidade visual moderna/minimalista;
- acessibilidade básica.

**Testes:**
- validar build;
- validar navegação principal;
- testes disponíveis para componentes críticos.

**Aceitação:**
- [x] desktop funciona;
- [x] tablet funciona;
- [x] mobile funciona;
- [x] navegação funciona;
- [x] labels estão associados aos campos;
- [x] elementos possuem semântica adequada;
- [x] navegação por teclado é possível nos fluxos principais;
- [x] build passa.

**Dependências:** T01.

---

### T20 — Frontend de autenticação
**Status:** `completed`

**Escopo:**
- cadastro;
- login;
- logout;
- armazenamento/remoção do JWT;
- sessão expirada;
- erros provenientes da API.

**Testes de feature/API de integração:**
- cadastro válido;
- cadastro inválido;
- login válido;
- login inválido;
- logout;
- sessão expirada.

**Aceitação:**
- [x] usuário consegue cadastrar;
- [x] usuário consegue entrar;
- [x] token é utilizado nas requisições protegidas;
- [x] logout remove o token;
- [x] sessão expirada é tratada;
- [x] erros da API são exibidos;
- [x] estados de loading/erro são tratados.

**Dependências:** T05, T19.

---

### T21 — Frontend de categorias
**Status:** `completed`

**Escopo:**
- telas/fluxos de CRUD de categorias;
- diferenciação visual de receita/despesa;
- feedback de sucesso/falha;
- estado vazio;
- loading.

**Testes:**
- criação;
- edição;
- exclusão;
- rejeição de exclusão de categoria utilizada.

**Aceitação:**
- [x] CRUD integrado à API;
- [x] feedback visual implementado;
- [x] loading implementado;
- [x] estado vazio implementado;
- [x] erros da API tratados.

**Dependências:** T07, T19.

---

### T22 — Frontend de transações
**Status:** `completed`

**Escopo:**
- criação;
- edição;
- exclusão;
- listagem;
- filtros;
- paginação;
- ordenação;
- diferenciação visual entre receitas e despesas.

**Testes:**
- criação válida;
- validação;
- edição;
- exclusão;
- filtros;
- paginação;
- erro da API.

**Aceitação:**
- [x] CRUD integrado;
- [x] filtros funcionam;
- [x] paginação funciona;
- [x] ordenação funciona;
- [x] receitas e despesas possuem diferenciação visual;
- [x] loading/erro/estado vazio tratados.

**Dependências:** T10, T19.

---

### T23 — Frontend de parcelamentos
**Status:** `completed`

**Escopo:**
- formulário de parcelamento;
- exibição/consulta das parcelas;
- integração com API.

**Testes:**
- criação;
- validação;
- distribuição;
- tratamento de erro.

**Aceitação:**
- [x] usuário consegue registrar despesa parcelada;
- [x] dados enviados correspondem à API;
- [x] feedback de sucesso/falha existe;
- [x] erros são tratados;
- [x] parcelas podem ser identificadas como pertencentes ao parcelamento.

**Dependências:** T11, T19.

---

### T24 — Frontend de recorrências
**Status:** `completed`

**Escopo:**
- criação e gerenciamento de recorrências;
- frequências;
- data inicial/final;
- configuração de dia útil quando aplicável.

**Testes:**
- criação;
- validação;
- frequência;
- data;
- erro da API.

**Aceitação:**
- [x] recorrência pode ser configurada;
- [x] frequências previstas são apresentadas;
- [x] datas são validadas;
- [x] regra de dia útil é representada quando aplicável;
- [x] feedback de operação existe.

**Dependências:** T16, T19.

---

### T25 — Dashboard frontend
**Status:** `completed`

**Escopo:**
- mês corrente como padrão;
- seletor de mês;
- indicadores;
- listagem do período;
- gráfico de pizza por categoria;
- estado vazio quando não houver despesas.

**Testes:**
- renderização com dados;
- troca de mês;
- estado sem despesas;
- loading;
- erro da API.

**Aceitação:**
- [x] mês corrente é apresentado por padrão;
- [x] usuário pode selecionar outro mês;
- [x] indicadores respeitam o mês;
- [x] transações respeitam o mês;
- [x] gráfico representa concentração por categoria;
- [x] ausência de despesas apresenta estado vazio;
- [x] divisão por zero não ocorre;
- [x] loading/erro são tratados.

**Dependências:** T18, T19.

---

## Fase 9 — Qualidade e aceite final

### T26 — Auditoria de isolamento de dados
**Status:** `completed`

**Objetivo:** validar sistematicamente a regra RB01 em todos os recursos.

**Escopo:**
- usuários;
- categorias;
- transações;
- recorrências;
- parcelamentos;
- dashboard.

**Testes de feature obrigatórios:**
- usuário A não lê recursos de B;
- usuário A não altera recursos de B;
- usuário A não exclui recursos de B;
- IDs conhecidos de B não permitem acesso;
- associações entre recursos também respeitam o proprietário.

**Aceitação:**
- [x] todos os recursos protegidos;
- [x] todos os testes cross-user passam;
- [x] nenhuma rota depende apenas do frontend para autorização.

**Dependências:** T06, T08, T11, T13, T18.

---

### T27 — Auditoria de API e respostas
**Status:** `completed`

**Escopo:**
- revisar HTTP verbs;
- revisar status codes;
- revisar JSON;
- revisar Resources;
- revisar erros;
- revisar validações;
- revisar endpoints protegidos.

**Testes de API obrigatórios:**
- sucesso;
- validação;
- autenticação;
- autorização;
- recurso inexistente;
- erro de regra de negócio.

**Aceitação:**
- [x] API usa verbs adequados;
- [x] status codes são coerentes;
- [x] respostas são padronizadas;
- [x] erros são padronizados;
- [x] Resources são utilizados;
- [x] testes passam.

**Dependências:** T05, T07, T10, T11, T13, T18.

---

### T28 — Auditoria de requisitos não funcionais
**Status:** `completed`

**Escopo:**
- segurança;
- responsividade;
- acessibilidade;
- loading;
- erro;
- estado vazio;
- sessão expirada;
- manutenção/separação de responsabilidades.

**Aceitação:**
- [x] senhas com hashing seguro;
- [x] JWT possui expiração;
- [x] endpoints protegidos exigem autenticação;
- [x] isolamento está coberto;
- [x] frontend é responsivo;
- [x] campos possuem labels;
- [x] navegação por teclado é suportada;
- [x] erros possuem feedback textual;
- [x] estados de loading/erro/vazio existem quando aplicáveis;
- [x] regras de negócio não estão acopladas à UI.

**Dependências:** T20, T21, T22, T23, T24, T25, T26.

---

### T29 — Suíte final de testes
**Status:** `completed`

**Objetivo:** executar e estabilizar toda a suíte automatizada antes do aceite final.

**Obrigatório:**
- testes unitários;
- testes de feature;
- testes de API;
- testes relacionados a regras financeiras;
- testes de isolamento.

**Aceitação:**
- [x] suíte unitária passa;
- [x] suíte de feature passa;
- [x] suíte de API passa;
- [x] regras financeiras passam;
- [x] isolamento passa;
- [x] não existem testes ignorados que sejam necessários para os requisitos implementados;
- [x] build do frontend passa.

**Dependências:** todas as tarefas de implementação.

---

### T30 — Revisão final de escopo
**Status:** `completed`

**Objetivo:** confirmar que o produto implementado corresponde ao escopo documentado.

**Checklist:**
- [x] todos os requisitos RF01–RF17 implementados;
- [x] todas as regras RB01–RB07 implementadas;
- [x] requisitos RNF01–RNF06 verificados;
- [x] critérios gerais de aceite atendidos;
- [x] critérios de autenticação atendidos;
- [x] critérios de categorias atendidos;
- [x] critérios de transações atendidos;
- [x] critérios de parcelamento atendidos;
- [x] critérios de recorrência atendidos;
- [x] critérios de dashboard atendidos;
- [x] nenhuma funcionalidade fora de escopo foi introduzida;
- [x] documentação continua refletindo o comportamento real.

**Dependências:** T29.

---

## Matriz resumida

| ID | Tarefa | Status | Dependências |
|---|---|---|---|
| T01 | Estrutura inicial | pending | — |
| T02 | Base de testes | pending | T01 |
| T03 | Cadastro | pending | T02 |
| T04 | Login JWT | pending | T03 |
| T05 | Logout e perfil | pending | T04 |
| T06 | CRUD categorias | pending | T04 |
| T07 | Proteção de categoria utilizada | pending | T06, T08 |
| T08 | CRUD transações | pending | T06 |
| T09 | Compatibilidade categoria/transação | pending | T06, T08 |
| T10 | Filtros de transações | pending | T08, T09 |
| T11 | Parcelamentos | pending | T08, T09 |
| T12 | Contabilização mensal de parcelas | pending | T11, T10 |
| T13 | CRUD recorrências | pending | T09 |
| T14 | Geração recorrente | pending | T13 |
| T15 | Dia útil e feriados | pending | T14 |
| T16 | Recorrência por dia útil | pending | T15 |
| T17 | Cálculos financeiros | pending | T08, T12, T14 |
| T18 | API dashboard | pending | T17, T10 |
| T19 | Estrutura frontend | pending | T01 |
| T20 | Frontend autenticação | pending | T05, T19 |
| T21 | Frontend categorias | pending | T07, T19 |
| T22 | Frontend transações | pending | T10, T19 |
| T23 | Frontend parcelamentos | pending | T11, T19 |
| T24 | Frontend recorrências | pending | T16, T19 |
| T25 | Frontend dashboard | pending | T18, T19 |
| T26 | Auditoria isolamento | pending | T06, T08, T11, T13, T18 |
| T27 | Auditoria API | pending | T05, T07, T10, T11, T13, T18 |
| T28 | Auditoria não funcional | pending | T20–T26 |
| T29 | Suíte final | pending | todas as implementações |
| T30 | Revisão final | pending | T29 |

## Regra de atualização

O agente deve atualizar o `Status` de cada tarefa conforme o trabalho avança.

Exemplo:

```text
Status: `in progress`
```

Após concluir implementação, testes e critérios:

```text
Status: `completed`
```

Se existir impedimento real:

```text
Status: `blocked`
```

A tarefa bloqueada deve registrar, no próprio conteúdo da tarefa, qual decisão ou dependência está impedindo sua execução.
