document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('todo-form');
  const taskInput = document.getElementById('task-input');
  const taskDate = document.getElementById('task-date');
  const taskPriority = document.getElementById('task-priority');
  const taskCategory = document.getElementById('task-category');
  const taskList = document.getElementById('task-list');
  const taskCount = document.getElementById('task-count');
  const filterBtns = document.querySelectorAll('.filter-btn');
  const dateDisplay = document.getElementById('current-date');
  const playlistUrlInput = document.getElementById('playlist-url');
  const playlistPriorityInput = document.getElementById('playlist-priority');
  const playlistTypeInput = document.getElementById('playlist-type');
  const playlistDateInput = document.getElementById('playlist-date');
  const importPlaylistBtn = document.getElementById('import-playlist-btn');
  const playlistStatus = document.getElementById('playlist-status');
  const selectAllTasksInput = document.getElementById('select-all-tasks');
  const deleteSelectedBtn = document.getElementById('delete-selected-btn');
  const deleteAllBtn = document.getElementById('delete-all-btn');

  const browserHost = window.location.hostname || 'localhost';
  const isLocalNetworkHost =
    browserHost === 'localhost' ||
    browserHost === '127.0.0.1' ||
    browserHost.startsWith('192.168.') ||
    browserHost.startsWith('10.') ||
    browserHost.startsWith('172.16.') ||
    browserHost.startsWith('172.17.') ||
    browserHost.startsWith('172.18.') ||
    browserHost.startsWith('172.19.') ||
    browserHost.startsWith('172.2') ||
    browserHost.startsWith('172.30.') ||
    browserHost.startsWith('172.31.');
  const backendOrigin = window.location.port === '3000' ? '' : (isLocalNetworkHost ? `http://${browserHost}:3000` : '');
  const API_BASE = backendOrigin ? `${backendOrigin}/api/tasks` : '/api/tasks';
  const IMPORT_API = `${API_BASE.replace(/\/tasks$/, '')}/import/youtube-playlist`;
  const BULK_DELETE_API = `${API_BASE}/bulk-delete`;
  const IMPORT_LIMIT = 300;
  let tasks = [];
  let currentFilter = 'all';
  const selectedTaskIds = new Set();

  const options = { weekday: 'long', month: 'short', day: 'numeric' };
  dateDisplay.textContent = new Date().toLocaleDateString('en-US', options);

  init();

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    addTask().catch(() => {});
  });

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentFilter = btn.dataset.filter;
      renderTasks();
    });
  });

  importPlaylistBtn.addEventListener('click', () => {
    importPlaylist().catch(() => {});
  });

  selectAllTasksInput.addEventListener('change', () => {
    const filteredTasks = getFilteredTasks();
    if (selectAllTasksInput.checked) {
      filteredTasks.forEach(task => selectedTaskIds.add(task.id));
    } else {
      filteredTasks.forEach(task => selectedTaskIds.delete(task.id));
    }
    renderTasks();
  });

  deleteSelectedBtn.addEventListener('click', () => {
    deleteSelectedTasks().catch(() => {});
  });

  deleteAllBtn.addEventListener('click', () => {
    deleteAllTasks().catch(() => {});
  });

  async function init() {
    await loadTasks();
    renderTasks();
  }

  async function loadTasks() {
    try {
      const response = await fetch(API_BASE);
      if (!response.ok) return;
      tasks = await response.json();
    } catch (_error) {
      tasks = [];
    }
  }

  async function addTask() {
    const text = taskInput.value.trim();
    if (!text) return;

    const newTask = {
      text,
      date: taskDate.value,
      priority: taskPriority.value,
      category: taskCategory.value,
      completed: false
    };

    try {
      const response = await fetch(API_BASE, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newTask)
      });
      if (!response.ok) return;

      const createdTask = await response.json();
      tasks.unshift(createdTask);
      renderTasks();
    } catch (_error) {
      return;
    }

    taskInput.value = '';
    taskDate.value = '';
    taskPriority.value = 'medium';
    taskCategory.value = 'personal';
    taskInput.focus();
  }

  async function toggleTask(id) {
    const task = tasks.find(item => item.id === id);
    if (!task) return;

    try {
      const response = await fetch(`${API_BASE}/${id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ completed: !task.completed })
      });
      if (!response.ok) return;

      const updatedTask = await response.json();
      tasks = tasks.map(item => item.id === id ? updatedTask : item);
      renderTasks();
    } catch (_error) {
      return;
    }
  }

  function deleteTask(id, element) {
    element.classList.add('removing');

    setTimeout(async () => {
      try {
        const response = await fetch(`${API_BASE}/${id}`, { method: 'DELETE' });
        if (!response.ok && response.status !== 204) return;
        tasks = tasks.filter(task => task.id !== id);
        selectedTaskIds.delete(id);
        renderTasks();
      } catch (_error) {
        return;
      }
    }, 300);
  }

  async function importPlaylist() {
    const playlistUrl = playlistUrlInput.value.trim();
    const playlistPriority = playlistPriorityInput.value.trim();
    const playlistType = playlistTypeInput.value.trim();
    const playlistDate = playlistDateInput.value;
    if (!playlistUrl) return;
    if (!playlistPriority || !playlistType) {
      playlistStatus.textContent = 'Select priority and type before import';
      return;
    }

    importPlaylistBtn.disabled = true;
    playlistStatus.textContent = 'Importing playlist...';

    try {
      const response = await fetch(IMPORT_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          url: playlistUrl,
          priority: playlistPriority,
          category: playlistType,
          date: playlistDate,
          maxVideos: IMPORT_LIMIT
        })
      });

      const data = await response.json();
      if (!response.ok) {
        playlistStatus.textContent = data.message || 'Import failed';
        return;
      }

      const importedTasks = data.tasks || [];
      tasks = [...importedTasks, ...tasks];
      renderTasks();
      if (data.partial) {
        playlistStatus.textContent = `Imported ${data.importedCount} videos (${data.source || 'fallback'}). ${data.message || ''}`.trim();
      } else {
        playlistStatus.textContent = `Imported ${data.importedCount} videos as tasks (${data.source || 'primary'}, limit ${data.requestedLimit || IMPORT_LIMIT})`;
      }
      playlistUrlInput.value = '';
      playlistPriorityInput.value = '';
      playlistTypeInput.value = '';
      playlistDateInput.value = '';
    } catch (_error) {
      const backendHint = backendOrigin || window.location.origin;
      playlistStatus.textContent = `Could not connect to import service (${backendHint})`;
    } finally {
      importPlaylistBtn.disabled = false;
    }
  }

  async function deleteSelectedTasks() {
    const ids = Array.from(selectedTaskIds);
    if (ids.length === 0) return;

    deleteSelectedBtn.disabled = true;
    try {
      const response = await fetch(BULK_DELETE_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids })
      });
      if (!response.ok) return;

      const idsSet = new Set(ids);
      tasks = tasks.filter(task => !idsSet.has(task.id));
      ids.forEach(id => selectedTaskIds.delete(id));
      renderTasks();
    } finally {
      deleteSelectedBtn.disabled = false;
    }
  }

  async function deleteAllTasks() {
    if (tasks.length === 0) return;

    deleteAllBtn.disabled = true;
    try {
      const response = await fetch(API_BASE, { method: 'DELETE' });
      if (!response.ok) return;
      tasks = [];
      selectedTaskIds.clear();
      renderTasks();
    } finally {
      deleteAllBtn.disabled = false;
    }
  }

  function getFilteredTasks() {
    let filteredTasks = tasks;
    if (currentFilter === 'active') filteredTasks = tasks.filter(t => !t.completed);
    if (currentFilter === 'completed') filteredTasks = tasks.filter(t => t.completed);
    return filteredTasks;
  }

  function updateBulkActionState(filteredTasks) {
    const filteredIds = filteredTasks.map(task => task.id);
    const selectedVisibleCount = filteredIds.filter(id => selectedTaskIds.has(id)).length;
    selectAllTasksInput.checked = filteredIds.length > 0 && selectedVisibleCount === filteredIds.length;
    selectAllTasksInput.indeterminate = selectedVisibleCount > 0 && selectedVisibleCount < filteredIds.length;
    deleteSelectedBtn.disabled = selectedTaskIds.size === 0;
    deleteAllBtn.disabled = tasks.length === 0;
  }

  function renderTasks() {
    taskList.innerHTML = '';
    
    const filteredTasks = getFilteredTasks();

    const pendingCount = tasks.filter(t => !t.completed).length;
    taskCount.textContent = pendingCount;
    updateBulkActionState(filteredTasks);

    filteredTasks.forEach(task => {
      const li = document.createElement('li');
      li.className = `task-item priority-${task.priority} ${task.completed ? 'completed' : ''}`;
      
      const formattedDate = task.date ? new Date(task.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'}) : 'No date';

      li.innerHTML = `
        <input type="checkbox" class="custom-checkbox" ${task.completed ? 'checked' : ''}>
        <div class="task-details">
          <div class="task-content">${task.text}</div>
          <div class="task-meta">
            ${task.date ? `<span><i class="far fa-calendar"></i> ${formattedDate}</span>` : ''}
            <span><i class="fas fa-tag"></i> ${task.category}</span>
          </div>
        </div>
        <button class="delete-btn"><i class="fas fa-trash"></i></button>
      `;

      const checkbox = li.querySelector('.custom-checkbox');
      checkbox.addEventListener('change', () => {
        toggleTask(task.id).catch(() => {});
      });

      const deleteBtn = li.querySelector('.delete-btn');
      deleteBtn.addEventListener('click', () => deleteTask(task.id, li));

      taskList.appendChild(li);
    });
  }
});
