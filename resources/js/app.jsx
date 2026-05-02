import './bootstrap';
import '../css/app.css';
import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Boxes, Building2, Check, LogOut, PackagePlus, Pencil, Plus, Save, Trash2 } from 'lucide-react';

const api = async (path, options = {}) => {
    const token = localStorage.getItem('mgawi_token');
    const response = await fetch(`/api${path}`, {
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
            ...(options.headers || {}),
        },
    });
    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(firstError || data.message || 'Request failed.');
    }

    return data;
};

function App() {
    const [token, setToken] = useState(localStorage.getItem('mgawi_token'));
    const [authMode, setAuthMode] = useState('login');
    const [user, setUser] = useState(null);
    const [company, setCompany] = useState(null);
    const [reports, setReports] = useState({ total_items: 0, total_quantity: 0, low_stock: 0 });
    const [items, setItems] = useState([]);
    const [message, setMessage] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const [companyName, setCompanyName] = useState('');
    const [form, setForm] = useState({ name: '', quantity: 0 });
    const [editingId, setEditingId] = useState(null);

    const editingItem = useMemo(() => items.find((item) => item.id === editingId), [items, editingId]);

    useEffect(() => {
        if (token) {
            loadDashboard();
        }
    }, [token]);

    useEffect(() => {
        if (editingItem) {
            setForm({ name: editingItem.name, quantity: editingItem.quantity });
        }
    }, [editingItem]);

    const saveToken = (value) => {
        localStorage.setItem('mgawi_token', value);
        setToken(value);
    };

    const clearNotices = () => {
        setMessage('');
        setError('');
    };

    const handleAuth = async (event) => {
        event.preventDefault();
        clearNotices();
        setLoading(true);
        const data = Object.fromEntries(new FormData(event.currentTarget));

        try {
            if (authMode === 'register') {
                const result = await api('/register', { method: 'POST', body: JSON.stringify(data) });
                setMessage(result.message);
                setAuthMode('verify');
            } else if (authMode === 'verify') {
                const result = await api('/verify-otp', { method: 'POST', body: JSON.stringify(data) });
                saveToken(result.token);
                setUser(result.user);
            } else {
                const result = await api('/login', { method: 'POST', body: JSON.stringify(data) });
                saveToken(result.token);
                setUser(result.user);
            }
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const loadDashboard = async () => {
        clearNotices();
        setLoading(true);

        try {
            const data = await api('/dashboard');
            setUser(data.user);
            setCompany(data.company);
            setReports(data.reports);
            setItems(data.items);
        } catch (err) {
            setError(err.message);
            logout();
        } finally {
            setLoading(false);
        }
    };

    const createCompany = async (event) => {
        event.preventDefault();
        clearNotices();
        setLoading(true);

        try {
            const result = await api('/company', { method: 'POST', body: JSON.stringify({ name: companyName }) });
            saveToken(result.token);
            setUser(result.user);
            setCompany(result.user.company);
            setCompanyName('');
            await loadDashboard();
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const saveItem = async (event) => {
        event.preventDefault();
        clearNotices();
        setLoading(true);
        const payload = { name: form.name.trim(), quantity: Number(form.quantity) };

        try {
            if (editingId) {
                await api(`/inventory/${editingId}`, { method: 'PUT', body: JSON.stringify(payload) });
                setEditingId(null);
            } else {
                await api('/inventory', { method: 'POST', body: JSON.stringify(payload) });
            }
            setForm({ name: '', quantity: 0 });
            await loadDashboard();
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const deleteItem = async (id) => {
        clearNotices();
        setLoading(true);

        try {
            await api(`/inventory/${id}`, { method: 'DELETE' });
            await loadDashboard();
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    };

    const logout = () => {
        localStorage.removeItem('mgawi_token');
        setToken(null);
        setUser(null);
        setCompany(null);
        setItems([]);
        setReports({ total_items: 0, total_quantity: 0, low_stock: 0 });
    };

    if (!token) {
        return (
            <main className="auth-shell">
                <div className="brand">
                    <div className="brand-mark"><Boxes size={22} /></div>
                    <div>
                        <h1>Mgawi Inventory</h1>
                        <p>Minimal inventory SAAS for company accounts.</p>
                    </div>
                </div>
                <section className="panel auth-panel">
                    <AuthForm mode={authMode} loading={loading} onSubmit={handleAuth} />
                    {message && <p className="alert">{message}</p>}
                    {error && <p className="alert error">{error}</p>}
                    <div className="switch">
                        {authMode !== 'login' && <button onClick={() => setAuthMode('login')}>Login</button>}
                        {authMode !== 'register' && <button onClick={() => setAuthMode('register')}>Register</button>}
                    </div>
                </section>
            </main>
        );
    }

    return (
        <main className="app">
            <div className="shell">
                <header className="topbar">
                    <div className="page-title">
                        <div className="brand-mark"><Boxes size={22} /></div>
                        <div>
                            <h1>Welcome, {user?.name || 'there'}</h1>
                            <p>{company ? company.name : 'Create your company to start tracking inventory.'}</p>
                        </div>
                    </div>
                    <div className="actions">
                        <button className="button secondary" onClick={loadDashboard} disabled={loading}>
                            <Check size={18} /> Refresh
                        </button>
                        <button className="button danger" onClick={logout}>
                            <LogOut size={18} /> Logout
                        </button>
                    </div>
                </header>

                {error && <p className="alert error">{error}</p>}
                {message && <p className="alert">{message}</p>}

                {!company ? (
                    <section className="panel auth-panel">
                        <div className="brand">
                            <div className="brand-mark"><Building2 size={22} /></div>
                            <div>
                                <h2>Create Company</h2>
                                <p>Your JWT will be refreshed with the company context.</p>
                            </div>
                        </div>
                        <form className="form" onSubmit={createCompany}>
                            <label className="field">
                                <span>Company name</span>
                                <input className="input" value={companyName} onChange={(event) => setCompanyName(event.target.value)} required maxLength="150" />
                            </label>
                            <button className="button" disabled={loading}>
                                <Save size={18} /> Save Company
                            </button>
                        </form>
                    </section>
                ) : (
                    <>
                        <section className="stats">
                            <div className="panel stat"><span>Items</span><strong>{reports.total_items}</strong></div>
                            <div className="panel stat"><span>Total Quantity</span><strong>{reports.total_quantity}</strong></div>
                            <div className="panel stat"><span>Low Stock</span><strong>{reports.low_stock}</strong></div>
                        </section>
                        <section className="dashboard-grid">
                            <div className="panel">
                                <div className="section-head">
                                    <h2>Inventory List</h2>
                                    <span className="muted">{items.length} records</span>
                                </div>
                                <div className="table-wrap">
                                    <InventoryTable items={items} onEdit={setEditingId} onDelete={deleteItem} />
                                </div>
                            </div>
                            <aside className="panel side-panel">
                                <h2>{editingId ? 'Edit Item' : 'Add Item'}</h2>
                                <form className="form" onSubmit={saveItem}>
                                    <label className="field">
                                        <span>Name</span>
                                        <input className="input" value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required maxLength="150" />
                                    </label>
                                    <label className="field">
                                        <span>Quantity</span>
                                        <input className="input" type="number" min="0" value={form.quantity} onChange={(event) => setForm({ ...form, quantity: event.target.value })} required />
                                    </label>
                                    <button className="button" disabled={loading}>
                                        {editingId ? <Save size={18} /> : <PackagePlus size={18} />} {editingId ? 'Save Item' : 'Add Item'}
                                    </button>
                                    {editingId && (
                                        <button type="button" className="button secondary" onClick={() => { setEditingId(null); setForm({ name: '', quantity: 0 }); }}>
                                            <Plus size={18} /> New Item
                                        </button>
                                    )}
                                </form>
                            </aside>
                        </section>
                    </>
                )}
            </div>
        </main>
    );
}

function AuthForm({ mode, loading, onSubmit }) {
    return (
        <form className="form" onSubmit={onSubmit}>
            <h2>{mode === 'register' ? 'Register' : mode === 'verify' ? 'Verify OTP' : 'Login'}</h2>
            {mode === 'register' && (
                <label className="field">
                    <span>Name</span>
                    <input className="input" name="name" required maxLength="120" />
                </label>
            )}
            <label className="field">
                <span>Email</span>
                <input className="input" name="email" type="email" required />
            </label>
            {mode === 'verify' ? (
                <label className="field">
                    <span>OTP</span>
                    <input className="input" name="otp" inputMode="numeric" minLength="6" maxLength="6" required />
                </label>
            ) : (
                <label className="field">
                    <span>Password</span>
                    <input className="input" name="password" type="password" required minLength="8" />
                </label>
            )}
            <button className="button" disabled={loading}>
                <Check size={18} /> Continue
            </button>
        </form>
    );
}

function InventoryTable({ items, onEdit, onDelete }) {
    if (!items.length) {
        return <div className="empty">No inventory items yet.</div>;
    }

    return (
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Quantity</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {items.map((item) => (
                    <tr key={item.id}>
                        <td>{item.name}</td>
                        <td>{item.quantity}</td>
                        <td>{new Date(item.updated_at).toLocaleString()}</td>
                        <td>
                            <span className="mini-actions">
                                <button className="icon-btn" title="Edit item" onClick={() => onEdit(item.id)}><Pencil size={16} /></button>
                                <button className="icon-btn delete" title="Delete item" onClick={() => onDelete(item.id)}><Trash2 size={16} /></button>
                            </span>
                        </td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

createRoot(document.getElementById('root')).render(<App />);
