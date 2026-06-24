import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import API_BASE from '../apiConfig';

const Login = () => {
  const navigate = useNavigate();
  const [credentials, setCredentials] = useState({ username: '', password: '' });
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setCredentials({ ...credentials, [name]: value });
    if (error) setError(''); // Clear error on change
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    setError('');

    if (!credentials.username || !credentials.password) {
      setError('Mohon lengkapi username dan password.');
      setIsLoading(false);
      return;
    }

    try {
      const response = await fetch(`${API_BASE}/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(credentials),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || 'Login gagal.');
      }

      // Simpan session (Token)
      localStorage.setItem('storyblast_token', data.token);
      localStorage.setItem('storyblast_user', JSON.stringify(data.user));
      
      // Redirect or Update State
      navigate('/dashboard'); 
    } catch (err) {
      setError(err.message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-950 px-4">
      <div className="max-w-md w-full space-y-8 p-10 bg-slate-900/50 backdrop-blur-xl rounded-3xl border border-slate-800 shadow-2xl animate-in zoom-in duration-500">
        <div className="text-center">
          <div className="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-brand-green/10 mb-6 group hover:scale-105 transition-transform duration-300">
             <span className="text-4xl">🔐</span>
          </div>
          <h2 className="text-3xl font-extrabold text-white tracking-tight">Login StoryBlast</h2>
          <p className="mt-2 text-sm text-slate-400">
            Masuk untuk mulai mengelola konten media sosial MI RaRa.
          </p>
        </div>

        <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
          {error && (
            <div className="p-4 bg-red-500/10 border border-red-500/20 text-red-400 text-sm rounded-xl animate-in fade-in slide-in-from-top-2">
              <span className="font-medium">Kesalahan:</span> {error}
            </div>
          )}

          <div className="space-y-4">
            <div className="space-y-1">
              <label htmlFor="username" className="text-sm font-medium text-slate-300 ml-1">Username</label>
              <input
                id="username"
                name="username"
                type="text"
                autoComplete="username"
                required
                className="appearance-none block w-full px-4 py-3 border border-slate-700 bg-slate-800/50 text-white rounded-xl placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green transition-all"
                placeholder="Ex: admin_mira"
                value={credentials.username}
                onChange={handleChange}
              />
            </div>

            <div className="space-y-1">
              <label htmlFor="password" className="text-sm font-medium text-slate-300 ml-1">Password</label>
              <input
                id="password"
                name="password"
                type="password"
                autoComplete="current-password"
                required
                className="appearance-none block w-full px-4 py-3 border border-slate-700 bg-slate-800/50 text-white rounded-xl placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-brand-green/50 focus:border-brand-green transition-all"
                placeholder="••••••••"
                value={credentials.password}
                onChange={handleChange}
              />
            </div>
          </div>

          <div>
            <button
              type="submit"
              disabled={isLoading}
              className={`group relative w-full flex justify-center py-3.5 px-4 border border-transparent text-sm font-bold rounded-xl text-white bg-brand-green hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-green transition-all active:scale-95 disabled:opacity-50 disabled:active:scale-100 ${isLoading ? 'cursor-not-allowed' : 'cursor-pointer'}`}
            >
              {isLoading ? (
                <div className="flex items-center space-x-2">
                   <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>Memproses...</span>
                </div>
              ) : 'Masuk ke Dashboard'}
            </button>
          </div>
        </form>

        <div className="pt-4 text-center">
          <p className="text-xs text-slate-500">
             Lupa password? Hubungi Superadmin Madrasah.
          </p>
        </div>
      </div>
    </div>
  );
};

export default Login;
