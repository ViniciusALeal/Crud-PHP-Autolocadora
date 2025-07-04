const app = document.getElementById('app');

function renderMenu() {
  app.innerHTML = `
    <h1>Bem-vindo</h1>
    <button id="btnEntrar">Entrar</button>
    <button id="btnCadastrar">Cadastrar</button>
    <button id="btnEditar">Editar Perfil</button>
    <p id="msg" style="color:red;"></p>
  `;

  document.getElementById('btnEntrar').onclick = renderLogin;
  document.getElementById('btnCadastrar').onclick = renderCadastro;
  document.getElementById('btnEditar').onclick = renderEditarPreliminar;
}

function renderLogin() {
  app.innerHTML = `
    <h2>Entrar</h2>
    <form id="formLogin">
      <input name="login" placeholder="Login" required /><br/>
      <input type="password" name="senha" placeholder="Senha" required /><br/>
      <button>Entrar</button>
    </form>
    <button id="btnVoltar">Voltar</button>
    <p id="msg" style="color:red;"></p>
  `;

  document.getElementById('formLogin').onsubmit = async e => {
    e.preventDefault();
    const data = new FormData(e.target);
    data.append('acao', 'login'); // obrigatório
    const msg = document.getElementById('msg');
    msg.textContent = '';
    try {
      const res = await fetch('login.php', { method: 'POST', body: data });
      const json = await res.json();
      if (json.ok) {
        window.location.href = 'index.php';
      } else {
        msg.textContent = json.msg || 'Erro no login';
      }
    } catch {
      msg.textContent = 'Erro no servidor';
    }
  };

  document.getElementById('btnVoltar').onclick = renderMenu;
}

function renderCadastro() {
  app.innerHTML = `
    <h2>Cadastrar Usuário</h2>
    <form id="formCadastro">
      <input name="login" placeholder="Login" required /><br/>
      <input name="nome" placeholder="Nome" required /><br/>
      <input name="email" type="email" placeholder="Email" required /><br/>
      <input type="password" name="senha" placeholder="Senha" required /><br/>
      <button>Cadastrar</button>
    </form>
    <button id="btnVoltar">Voltar</button>
    <p id="msg" style="color:red;"></p>
  `;

  document.getElementById('formCadastro').onsubmit = async e => {
    e.preventDefault();
    const data = new FormData(e.target);
    data.append('acao', 'criar');
    const msg = document.getElementById('msg');
    msg.textContent = '';
    try {
      const res = await fetch('login.php', { method: 'POST', body: data });
      const json = await res.json();
      if (json.ok) {
        alert('Usuário criado com sucesso! Faça login.');
        renderLogin();
      } else {
        msg.textContent = json.msg || 'Erro ao cadastrar';
      }
    } catch {
      msg.textContent = 'Erro no servidor';
    }
  };

  document.getElementById('btnVoltar').onclick = renderMenu;
}

function renderEditarPreliminar() {
  app.innerHTML = `
    <h2>Editar Perfil</h2>
    <p>Informe seu login e email para liberar edição</p>
    <form id="formEditarPreliminar">
      <input name="login" placeholder="Login" required /><br/>
      <input name="email" type="email" placeholder="Email" required /><br/>
      <button>Confirmar</button>
    </form>
    <button id="btnVoltar">Voltar</button>
    <p id="msg" style="color:red;"></p>
  `;

  document.getElementById('formEditarPreliminar').onsubmit = async e => {
    e.preventDefault();
    const data = new FormData(e.target);
    data.append('acao', 'checkEditar');
    const msg = document.getElementById('msg');
    msg.textContent = '';
    try {
      const res = await fetch('login.php', { method: 'POST', body: data });
      const json = await res.json();
      if (json.ok) {
        renderEditar(json.usuario);
      } else {
        msg.textContent = json.msg || 'Login ou email incorretos';
      }
    } catch {
      msg.textContent = 'Erro no servidor';
    }
  };

  document.getElementById('btnVoltar').onclick = renderMenu;
}

function renderEditar(usuario) {
  app.innerHTML = `
    <h2>Editando Perfil: ${usuario.login}</h2>
    <form id="formEditar">
      <input name="nome" value="${usuario.nome}" required /><br/>
      <input name="email" type="email" value="${usuario.email}" required /><br/>
      <input type="password" name="senha" value="${usuario.senha}" required /><br/>
      <button>Salvar</button>
    </form>
    <button id="btnVoltar">Voltar</button>
    <p id="msg" style="color:green;"></p>
  `;

  document.getElementById('formEditar').onsubmit = async e => {
    e.preventDefault();
    const data = new FormData(e.target);
    data.append('acao', 'editar');
    data.append('login', usuario.login); // para backend saber quem editar

    const msg = document.getElementById('msg');
    msg.textContent = '';

    try {
      const res = await fetch('login.php', { method: 'POST', body: data });
      const json = await res.json();
      if (json.ok) {
        msg.style.color = 'green';
        msg.textContent = json.msg || 'Alterações salvas';
      } else {
        msg.style.color = 'red';
        msg.textContent = json.msg || 'Erro ao salvar';
      }
    } catch {
      msg.style.color = 'red';
      msg.textContent = 'Erro no servidor';
    }
  };

  document.getElementById('btnVoltar').onclick = renderMenu;
}

renderMenu();
