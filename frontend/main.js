const API_AUTH = "../backend/auth.php";
const API_TASKS = "../backend/tasks.php";

// Variável para armazenar o token JWT. user_id não é mais armazenado.
let editingTaskId = null;

// Função auxiliar para obter o token do armazenamento local
function getToken() {
    return localStorage.getItem('jwt_token');
}

// Função auxiliar para criar os cabeçalhos de requisição, incluindo o Authorization
function getHeaders(contentType = "application/json") {
    const headers = {
        "Content-Type": contentType
    };
    const token = getToken();
    if (token) {
        headers["Authorization"] = `Bearer ${token}`;
    }
    return headers;
}

// Mostrar/ocultar telas
function hideAll() {
    document.querySelectorAll("#app>div").forEach(d => d.classList.add("hidden"));
}
function showLogin() { hideAll(); document.getElementById("login").classList.remove("hidden"); }
function showRegister() { hideAll(); document.getElementById("register").classList.remove("hidden"); }
// showMain agora verifica o token
function showMain() { 
    if (!getToken()) {
        return showLogin(); // Volta para o login se não houver token
    }
    hideAll(); 
    document.getElementById("main").classList.remove("hidden"); 
    loadTasks(); 
}
function showCredits() { hideAll(); document.getElementById("credits").classList.remove("hidden"); }
function showTaskScreen(task = null) {
    hideAll();
    document.getElementById("task_screen").classList.remove("hidden");

    editingTaskId = task ? task.id : null;
    document.getElementById("task_screen_title").textContent = task ? "Editar Tarefa" : "Nova Tarefa";
    document.getElementById("task_titulo").value = task ? task.titulo : "";
    document.getElementById("task_desc").value = task ? task.descricao : "";
    document.getElementById("task_due").value = task && task.data_conclusao ? task.data_conclusao : "";
    document.getElementById("task_status").value = task ? task.status : "aberta";
}
function cancelTask() { showMain(); editingTaskId = null; }

// Registro
async function register() {
    const data = {
        nome: document.getElementById("reg_nome").value,
        sobrenome: document.getElementById("reg_sobrenome").value,
        nascimento: document.getElementById("reg_nascimento").value,
        login: document.getElementById("reg_login").value,
        senha: document.getElementById("reg_senha").value
    };
    await fetch(`${API_AUTH}?action=register`, {
        method: "POST",
        headers: getHeaders(), // Usa Content-Type padrão
        body: JSON.stringify(data)
    });
    alert("Usuário cadastrado!");
    showLogin();
}

// Login
async function login() {
    const data = {
        login: document.getElementById("login_user").value,
        senha: document.getElementById("login_pass").value
    };
    const res = await fetch(`${API_AUTH}?action=login`, {
        method: "POST",
        headers: getHeaders(), // Usa Content-Type padrão
        body: JSON.stringify(data)
    });
    const json = await res.json();
    
    if (json.success && json.token) {
        // --- NOVO: Armazena o token JWT ---
        localStorage.setItem('jwt_token', json.token); 
        showMain();
    } else {
        alert(json.error || "Erro no login!");
    }
}

// Carregar tarefas
async function loadTasks() {
    // Tenta carregar tarefas enviando o token no cabeçalho
    const res = await fetch(`${API_TASKS}?action=list`, { headers: getHeaders() });
    
    // TRATAMENTO DE ERRO: Verifica se a resposta HTTP NÃO é de sucesso (ex: 401, 500)
    if (!res.ok) {
        // Tenta ler o corpo da resposta como JSON (pode ser o erro {"error": "..."})
        const errorData = await res.json().catch(() => ({ error: "Erro desconhecido" }));
        console.error("Falha ao carregar tarefas:", errorData);

        if (res.status === 401) {
            // Se for 401, a sessão expirou
            alert("Sessão expirada. Faça login novamente.");
            return logout();
        }
        
        // Se for outro erro, limpa a lista e encerra a função
        document.getElementById("task_list").innerHTML = "<li>Erro ao processar a lista de tarefas. Tente novamente mais tarde.</li>";
        return; 
    }

    // Se a resposta for OK (HTTP 200), processa o JSON
    const tasks = await res.json(); 
    
    // GARANTIA: Verifica se tasks é um array antes de usar forEach
    if (!Array.isArray(tasks)) {
        console.error("Resposta da API não é uma lista de tarefas:", tasks);
        document.getElementById("task_list").innerHTML = "<li>Erro ao processar a lista de tarefas.</li>";
        return;
    }

    const list = document.getElementById("task_list");
    list.innerHTML = "";

    const today = new Date();

    tasks.forEach(t => {
        const li = document.createElement("li");
        li.dataset.id = t.id;

        // Info tarefa
        const infoDiv = document.createElement("div");
        infoDiv.classList.add("task-info");

        const title = document.createElement("div");
        title.classList.add("task-title");
        title.textContent = t.titulo;

        const date = document.createElement("div");
        date.classList.add("task-date");

        // Ajuste definitivo da data para dd/mm/yy
        if (t.data_conclusao) {
            const parts = t.data_conclusao.split('-'); // YYYY-MM-DD
            const day = parts[2];
            const month = parts[1];
            const year = parts[0].slice(-2);
            date.textContent = `Prazo: ${day}/${month}/${year}`;
        } else {
            date.textContent = '';
        }

        infoDiv.appendChild(title);
        infoDiv.appendChild(date);
        li.appendChild(infoDiv);

        // Status atrasada/concluida
        if (t.status === "aberta" && t.data_conclusao) {
            const d = new Date(t.data_conclusao);
            const now = new Date();
            now.setHours(0, 0, 0, 0);
            d.setHours(0, 0, 0, 0);
            if (d < now) li.classList.add("atrasada");
        }
        if (t.status === "concluida") li.classList.add("concluida");

        // Botões
        const btnDiv = document.createElement("div");
        btnDiv.classList.add("task-buttons");

        const editBtn = document.createElement("button");
        editBtn.innerHTML = '<i class="fa fa-edit"></i>';
        editBtn.title = "Editar";
        editBtn.onclick = () => showTaskScreen(t);

        const deleteBtn = document.createElement("button");
        deleteBtn.innerHTML = '<i class="fa fa-trash"></i>';
        deleteBtn.title = "Deletar";
        deleteBtn.onclick = () => deleteTask(t.id);

        const toggleBtn = document.createElement("button");
        toggleBtn.innerHTML = t.status === "aberta" ? '<i class="fa fa-check"></i>' : '<i class="fa fa-undo"></i>';
        toggleBtn.title = t.status === "aberta" ? "Concluir" : "Reabrir";
        toggleBtn.onclick = () => updateStatus(t.id, t.status === "aberta" ? "concluida" : "aberta");

        btnDiv.appendChild(editBtn);
        btnDiv.appendChild(deleteBtn);
        btnDiv.appendChild(toggleBtn);
        li.appendChild(btnDiv);

        list.appendChild(li);
    });
}

// Salvar tarefa
async function saveTask() {
    const data = {
        // user_id não é mais necessário aqui
        titulo: document.getElementById("task_titulo").value,
        descricao: document.getElementById("task_desc").value,
        data_conclusao: document.getElementById("task_due").value,
        status: document.getElementById("task_status").value
    };

    if (editingTaskId) {
        data.id = editingTaskId;
        // --- NOVO: Envia o token no cabeçalho ---
        await fetch(`${API_TASKS}?action=edit`, {
            method: "POST",
            headers: getHeaders(),
            body: JSON.stringify(data)
        });
    } else {
        // --- NOVO: Envia o token no cabeçalho ---
        await fetch(`${API_TASKS}?action=add`, {
            method: "POST",
            headers: getHeaders(),
            body: JSON.stringify(data)
        });
    }
    showMain();
}

// Deletar / atualizar
async function deleteTask(id) {
    if (!confirm("Deseja realmente deletar esta tarefa?")) return;
    // --- NOVO: Envia o token no cabeçalho ---
    await fetch(`${API_TASKS}?action=delete`, {
        method: "POST",
        headers: getHeaders(),
        body: JSON.stringify({ id })
    });
    loadTasks();
}

async function updateStatus(id, status) {
    // --- NOVO: Envia o token no cabeçalho ---
    await fetch(`${API_TASKS}?action=update_status`, {
        method: "POST",
        headers: getHeaders(),
        body: JSON.stringify({ id, status })
    });
    loadTasks();
}

// Logout
function logout() { 
    localStorage.removeItem('jwt_token'); // --- NOVO: Remove o token ---
    showLogin(); 
}

// Inicialização
hideAll();
showMain(); // Tenta carregar a tela principal (vai verificar o token)