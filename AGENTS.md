# AGENTS.md — Cost Visor

> **Fonte de verdade operacional para agentes de IA.**
>
> O comportamento do sistema deve ser derivado da especificação funcional fornecida para o projeto. Este arquivo traduz essa especificação em regras de execução para agentes. Em caso de conflito entre implementação existente e requisitos documentados, os requisitos documentados prevalecem.

## 1. Objetivo do agente

Implementar o Cost Visor como uma aplicação web de gestão financeira pessoal, mantendo separação clara entre backend e frontend e preservando integralmente as regras de negócio especificadas.

O agente deve:

1. entender os requisitos antes de implementar;
2. implementar em pequenas unidades verificáveis;
3. criar testes junto com cada regra de negócio;
4. executar os testes após cada mudança relevante;
5. não introduzir comportamento não especificado;
6. manter o isolamento de dados entre usuários;
7. atualizar a documentação quando uma decisão de implementação ainda não estiver definida.

## 2. Fonte de verdade e precedência

A especificação funcional do projeto é a autoridade sobre o comportamento esperado.

Use a seguinte precedência:

1. requisitos funcionais e regras de negócio da especificação;
2. requisitos não funcionais da especificação;
3. critérios de aceite da especificação;
4. decisões técnicas explicitamente documentadas;
5. convenções do framework e do projeto.

Não use preferências do agente para alterar regras de negócio.

Se uma decisão técnica for necessária e a especificação não a definir, o agente deve:

- escolher a alternativa tecnicamente coerente com a arquitetura existente;
- documentar a decisão;
- não transformar a decisão técnica em nova regra de negócio;
- não alterar o comportamento funcional esperado.

Se a implementação exigir uma decisão que altere o comportamento funcional, a tarefa deve ser marcada como `blocked` até que a especificação seja atualizada.

## 3. Arquitetura obrigatória

A raiz deve possuir:

```text
/backend
/frontend
```

### Backend

O backend é Laravel + PHP e funciona exclusivamente como API REST.

Responsabilidades:

- autenticação;
- autorização;
- validação;
- regras de negócio;
- persistência;
- recorrências;
- parcelamentos;
- cálculos financeiros;
- respostas consumidas pelo frontend.

Não criar páginas HTML de usuário no backend.

### Frontend

O frontend é React + TypeScript com shadcn/ui.

Responsabilidades:

- UI;
- navegação;
- comunicação com API;
- apresentação;
- estado;
- feedback visual;
- validações de experiência.

Não mover regras de negócio do backend para componentes visuais.

## 4. Stack obrigatória

Backend:

- PHP;
- Laravel;
- REST;
- JWT;
- banco relacional.

Banco:

- SQLite.

Frontend:

- React;
- TypeScript;
- shadcn/ui.

Não substituir essas tecnologias sem alteração documentada da especificação.

## 5. Domínio

### Usuário

Possui conta própria e só pode acessar recursos pertencentes à própria conta.

### Categoria

Campos:

- nome;
- cor;
- tipo.

Tipos permitidos:

```text
RECEITA
DESPESA
```

Regras:

- nome obrigatório;
- cor obrigatória e válida;
- tipo obrigatório;
- pertence a um usuário;
- outro usuário não pode acessá-la;
- categoria usada por transações não pode ser excluída.

### Transação

Campos:

- valor;
- descrição;
- data;
- tipo;
- categoria.

Tipos:

```text
RECEITA
DESPESA
```

Regras:

- valor obrigatório e maior que zero;
- descrição obrigatória;
- data obrigatória;
- tipo obrigatório;
- categoria obrigatória;
- categoria pertence ao usuário;
- tipo da categoria deve ser igual ao tipo da transação.

### Recorrência

Campos:

- valor;
- descrição;
- tipo;
- categoria;
- frequência;
- data inicial;
- data final opcional.

Frequências:

- diária;
- semanal;
- mensal;
- anual.

As transações geradas devem respeitar frequência, datas, categoria, tipo e valor.

### Dia útil

Regra exata:

```text
dia útil = qualquer dia que não seja domingo ou feriado
```

Consequências:

- domingo: não útil;
- feriado: não útil;
- sábado: útil.

O cálculo utiliza o calendário de feriados configurado pela aplicação.

A estratégia técnica para manutenção desse calendário pode ser definida durante a implementação, desde que não altere essa regra.

### Parcelamento

Entrada:

- valor total;
- quantidade de parcelas;
- data da primeira parcela;
- descrição;
- categoria.

Regras:

- somente despesas;
- cada parcela é uma transação individual;
- cada parcela possui sua própria data;
- cada parcela é contabilizada no mês da sua data;
- parcelas mantêm relação lógica com o parcelamento original;
- soma das parcelas corresponde ao total, respeitando eventuais regras de arredondamento;
- outro usuário não pode acessar as parcelas.

Não transformar o parcelamento em uma única transação para fins de contabilização.

### Saldo

Existe um único saldo global por usuário.

Fórmula:

```text
saldo = receitas - despesas
```

Não implementar contas financeiras, bancos, carteiras, cartões ou instituições como dimensões de saldo nesta versão.

## 6. Autenticação e autorização

Cadastro:

- nome obrigatório;
- e-mail obrigatório;
- e-mail válido;
- e-mail único;
- senha obrigatória;
- requisitos mínimos de segurança;
- senha nunca em texto puro.

Login:

- e-mail;
- senha;
- retorno de JWT em autenticação bem-sucedida.

Frontend usa JWT para endpoints protegidos.

Logout:

- frontend remove o token armazenado localmente;
- mecanismo de invalidação/revogação do JWT deve ser definido na implementação do backend.

O JWT deve possuir expiração.

## 7. Isolamento de dados — regra crítica

Toda entidade financeira deve estar vinculada ao usuário correto.

Nunca confiar apenas em IDs enviados pelo frontend.

Para qualquer operação protegida, o backend deve verificar que o recurso pertence ao usuário autenticado.

Devem existir testes para:

- leitura indevida;
- atualização indevida;
- exclusão indevida;
- associação indevida de categoria;
- acesso indevido a recorrência;
- acesso indevido a parcelas.

Conhecer o ID de outro usuário não pode permitir acesso.

## 8. Dashboard

Por padrão, o dashboard usa o mês corrente.

O usuário pode selecionar outro mês.

O mês selecionado deve ser aplicado a todos os indicadores, gráficos e listagens do dashboard.

Indicadores:

- saldo;
- total de receitas;
- total de despesas;
- quantidade de transações.

Transações do período:

- receitas e despesas;
- descrição;
- categoria;
- valor;
- data;
- ordenação por data.

Concentração de despesas:

```text
percentual =
(soma das despesas da categoria / soma total das despesas) × 100
```

Regras:

- agrupar por categoria;
- percentuais devem representar 100%, considerando arredondamentos;
- total de despesas igual a zero não pode causar divisão por zero;
- nesse caso, frontend apresenta estado vazio.

## 9. Consulta de transações

Filtros obrigatórios da funcionalidade:

- mês;
- período;
- tipo;
- categoria;
- descrição.

Também deve haver:

- ordenação por data;
- paginação.

Ordenação padrão:

```text
mais recente → mais antiga
```

Receitas e despesas devem ter diferenciação visual.

## 10. API REST

A API deve:

- usar HTTP verbs adequados;
- usar códigos HTTP apropriados;
- retornar JSON;
- possuir respostas padronizadas;
- possuir erros padronizados;
- validar todos os dados recebidos;
- usar Laravel Resources.

Controllers devem permanecer finos. Regras de negócio complexas não devem ser concentradas nos controllers.

A organização interna pode utilizar serviços, casos de uso, actions, policies ou outras abstrações adequadas, desde que preserve separação de responsabilidades e testabilidade.

## 11. Frontend

A UI deve ser:

- moderna;
- minimalista;
- responsiva;
- legível;
- acessível;
- predominantemente escura com elementos de contraste.

Quando aplicável, toda operação deve possuir:

- loading;
- erro;
- estado vazio;
- sucesso;
- falha;
- tratamento de sessão expirada;
- apresentação de erros da API.

Não duplicar regras de negócio do backend apenas para montar a interface.

## 12. Segurança

Obrigatório:

- hashing seguro de senhas;
- endpoints protegidos exigem autenticação;
- autorização por proprietário;
- JWT com expiração;
- ausência de dados financeiros em endpoints públicos;
- validação backend independente do frontend.

## 13. Testes — obrigatório

Nenhuma tarefa que introduza ou altere regra de negócio pode ser considerada `completed` sem testes adequados.

### Unitários

Devem cobrir, no mínimo, regras isoladas de:

- saldo;
- percentual por categoria;
- parcelamento;
- dia útil;
- datas de recorrência.

### Feature

Devem cobrir fluxos:

- cadastro;
- login;
- logout;
- categorias;
- transações;
- recorrências;
- parcelamentos;
- dashboard.

### API

Devem validar:

- HTTP status;
- JSON;
- payloads;
- validação;
- autenticação;
- autorização;
- isolamento.

### Regra de evolução

Toda nova regra de negócio exige teste automatizado correspondente.

Quando uma tarefa envolver backend e frontend, a parte de domínio deve ser protegida por testes no backend; a integração visual deve ser validada conforme a estrutura de testes disponível no projeto.

## 14. Critérios gerais para concluir uma tarefa

Uma tarefa só pode ser marcada como `completed` quando:

- implementação concluída;
- validação adequada implementada;
- autorização adequada implementada quando aplicável;
- testes automatizados executados e aprovados;
- endpoint documentado quando aplicável;
- integração frontend concluída quando aplicável;
- loading/erro/estado vazio tratados quando aplicável;
- comportamento respeita a especificação.

Se um requisito estiver implementado, mas os testes obrigatórios não existirem ou falharem, o status não é `completed`.

## 15. Estados das tarefas

Use somente:

- `pending` — ainda não iniciada;
- `in progress` — em desenvolvimento;
- `completed` — implementação e critérios de aceite concluídos;
- `blocked` — não pode avançar sem resolver dependência, ambiguidade ou decisão necessária.

Não marcar tarefa como concluída apenas porque o código compila.

## 16. Procedimento de execução no OpenCode

Para cada tarefa:

1. ler esta documentação;
2. ler a tarefa correspondente em `TASKS.md`;
3. verificar dependências;
4. inspecionar o código existente antes de criar novos arquivos;
5. implementar somente o escopo da tarefa;
6. criar/atualizar testes unitários;
7. criar/atualizar testes de feature/API;
8. executar os testes;
9. corrigir falhas introduzidas pela implementação;
10. verificar critérios de aceite;
11. atualizar o status da tarefa;
12. somente então avançar para a próxima tarefa.

Não implementar tarefas futuras antecipadamente se isso alterar escopo, dependências ou comportamento.

## 17. Não fazer

O agente não deve:

- implementar funcionalidades fora do escopo;
- adicionar integrações bancárias;
- adicionar Open Finance;
- adicionar importação automática de extratos;
- adicionar sincronização com cartões;
- adicionar investimentos;
- adicionar patrimônio;
- adicionar múltiplas moedas;
- adicionar pagamentos;
- adicionar compartilhamento financeiro;
- adicionar múltiplas contas;
- adicionar integrações financeiras externas;
- inventar regras de negócio;
- permitir acesso cross-user;
- colocar regras de negócio em componentes visuais;
- considerar frontend como camada de segurança;
- marcar tarefas como concluídas sem testes.

## 18. Evolução da especificação

Se uma mudança de comportamento for necessária:

1. identificar o requisito afetado;
2. registrar a decisão na documentação;
3. atualizar `TASKS.md` se necessário;
4. somente depois alterar o código.

A documentação deve continuar sendo a fonte de verdade do comportamento do sistema.
