import './bootstrap';

const selectors = {
    loginForm: '#login-form',
    email: '#email',
    password: '#password',
    loginError: '#login-error',
    loginSuccess: '#login-success',
    products: '#products',
    searchInput: '#search-input',
    searchButton: '#search-button',
    uploadForm: '#upload-form',
    uploadFile: '#upload-file',
    uploadStatus: '#upload-status',
    logoutButton: '#logout-button',
    wsStatus: '#ws-status',
    wsMessages: '#ws-messages',
};

const state = {
    token: null,
    ws: null,
};

const getEl = (selector) => document.querySelector(selector);

const setAuthToken = (token) => {
    state.token = token;
    if (token) {
        localStorage.setItem('auth_token', token);
        window.axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    } else {
        localStorage.removeItem('auth_token');
        delete window.axios.defaults.headers.common.Authorization;
    }
};

const show = (el) => el.classList.remove('hidden');
const hide = (el) => el.classList.add('hidden');

const renderProducts = (data) => {
    const productsEl = getEl(selectors.products);
    if (!data || !data.data || data.data.length === 0) {
        productsEl.textContent = 'No products found.';
        return;
    }

    productsEl.innerHTML = data.data
        .map(
            (item) =>
                `<div class="flex justify-between border-b border-slate-800 py-2">
                    <div>${item.product_id}</div>
                    <div class="text-slate-400">${item.brand} ${item.model}</div>
                    <div class="text-emerald-400">${item.quantity}</div>
                </div>`
        )
        .join('');
};

const fetchProducts = async (search) => {
    const productsEl = getEl(selectors.products);
    productsEl.textContent = 'Loading products...';
    const response = await window.axios.get('/api/products', {
        params: search ? { search } : {},
    });
    renderProducts(response.data);
};

const connectWebSocket = (token) => {
    const wsStatus = getEl(selectors.wsStatus);
    const wsMessages = getEl(selectors.wsMessages);
    if (!token) {
        wsStatus.textContent = 'Disconnected';
        return;
    }

    if (state.ws) {
        state.ws.close();
    }

    const wsUrl = `ws://localhost:8081/?token=${encodeURIComponent(token)}`;
    const ws = new WebSocket(wsUrl);
    state.ws = ws;

    ws.onopen = () => {
        wsStatus.textContent = 'Connected';
    };

    ws.onmessage = (event) => {
        const entry = document.createElement('div');
        entry.textContent = event.data;
        wsMessages.prepend(entry);
    };

    ws.onclose = () => {
        wsStatus.textContent = 'Disconnected';
    };
};

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = getEl(selectors.loginForm);
    if (!loginForm) return;

    const loginError = getEl(selectors.loginError);
    const loginSuccess = getEl(selectors.loginSuccess);
    const uploadForm = getEl(selectors.uploadForm);
    const uploadStatus = getEl(selectors.uploadStatus);
    const logoutButton = getEl(selectors.logoutButton);
    const searchInput = getEl(selectors.searchInput);
    const searchButton = getEl(selectors.searchButton);

    const storedToken = localStorage.getItem('auth_token');
    if (storedToken) {
        setAuthToken(storedToken);
        show(logoutButton);
        show(uploadForm);
        fetchProducts();
        connectWebSocket(storedToken);
    }

    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        hide(loginError);
        hide(loginSuccess);

        try {
            const response = await window.axios.post('/api/login', {
                email: getEl(selectors.email).value,
                password: getEl(selectors.password).value,
            });

            setAuthToken(response.data.token);
            show(loginSuccess);
            loginSuccess.textContent = 'Login successful.';
            show(logoutButton);
            show(uploadForm);
            await fetchProducts();
            connectWebSocket(response.data.token);
        } catch (error) {
            show(loginError);
            loginError.textContent = error.response?.data?.message || 'Login failed.';
        }
    });

    logoutButton.addEventListener('click', async () => {
        try {
            await window.axios.post('/api/logout');
        } finally {
            setAuthToken(null);
            hide(logoutButton);
            hide(uploadForm);
            getEl(selectors.products).textContent = 'Login to load products.';
            connectWebSocket(null);
        }
    });

    searchButton.addEventListener('click', async () => {
        if (!state.token) return;
        await fetchProducts(searchInput.value.trim());
    });

    uploadForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!state.token) return;
        uploadStatus.textContent = 'Uploading...';
        const file = getEl(selectors.uploadFile).files[0];
        if (!file) {
            uploadStatus.textContent = 'Please choose a file.';
            return;
        }
        const formData = new FormData();
        formData.append('file', file);
        try {
            const response = await window.axios.post('/api/products/upload', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            uploadStatus.textContent = response.data.message || 'Upload queued.';
        } catch (error) {
            uploadStatus.textContent = error.response?.data?.message || 'Upload failed.';
        }
    });
});
