import React, { useState, useEffect } from 'react';
import API_BASE from '../apiConfig';

const UserManagement = () => {
  const [users, setUsers] = useState([]);
  const [isLoading, setIsLoading] = useState(true);

  const fetchUsers = async () => {
    try {
      const token = localStorage.getItem('storyblast_token');
      const response = await fetch(`${API_BASE}/users`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      const data = await response.json();
      if (response.ok) setUsers(data);
    } catch (error) {
      console.error('Fetch users error:', error);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchUsers();
  }, []);

  const handleToggleStatus = async (userId) => {
    try {
      const token = localStorage.getItem('storyblast_token');
      const response = await fetch(`${API_BASE}/users/${userId}/toggle`, {
        method: 'PATCH',
        headers: { 'Authorization': `Bearer ${token}` }
      });
      if (response.ok) fetchUsers();
    } catch (error) {
      console.error('Toggle status error:', error);
    }
  };

  return (
    <div className="max-w-6xl mx-auto p-6 space-y-8 animate-in fade-in duration-500">
      <header className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-extrabold text-white">Manajemen User</h1>
          <p className="text-slate-400 mt-1">Kelola akses administrator dan operator StoryBlast.</p>
        </div>
        <button className="px-5 py-2.5 bg-brand-green hover:bg-emerald-600 text-white font-bold rounded-xl transition-all">
          + User Baru
        </button>
      </header>

      <div className="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-2xl">
        <table className="w-full text-left border-collapse">
          <thead>
            <tr className="bg-slate-800/50 text-slate-400 text-xs uppercase tracking-widest">
              <th className="px-6 py-4 font-bold">Username</th>
              <th className="px-6 py-4 font-bold">Role</th>
              <th className="px-6 py-4 font-bold">Status</th>
              <th className="px-6 py-4 font-bold">Created At</th>
              <th className="px-6 py-4 font-bold text-right">Aksi</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-800">
            {users.map(user => (
              <tr key={user.id} className="hover:bg-slate-800/30 transition-colors">
                <td className="px-6 py-4">
                  <div className="font-bold text-white">{user.username}</div>
                  <div className="text-[10px] text-slate-500">ID: {user.id}</div>
                </td>
                <td className="px-6 py-4">
                   <span className={`px-3 py-1 rounded-full text-[10px] font-black uppercase ${user.role === 'superadmin' ? 'bg-purple-500/10 text-purple-400' : 'bg-blue-500/10 text-blue-400'}`}>
                      {user.role}
                   </span>
                </td>
                <td className="px-6 py-4">
                  <div className="flex items-center gap-2">
                    <div className={`w-2 h-2 rounded-full ${user.is_active ? 'bg-emerald-500' : 'bg-red-500'}`}></div>
                    <span className="text-sm text-slate-300">{user.is_active ? 'Aktif' : 'Nonaktif'}</span>
                  </div>
                </td>
                <td className="px-6 py-4 text-sm text-slate-400">
                  {new Date(user.created_at).toLocaleDateString()}
                </td>
                <td className="px-6 py-4 text-right space-x-2">
                  <button 
                    onClick={() => handleToggleStatus(user.id)}
                    className={`text-xs font-bold px-4 py-2 rounded-lg transition-all ${user.is_active ? 'bg-red-500/10 text-red-400 hover:bg-red-500/20' : 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20'}`}
                  >
                    {user.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {isLoading && (
          <div className="p-20 text-center text-slate-500 animate-pulse">Memuat data user...</div>
        )}
      </div>
    </div>
  );
};

export default UserManagement;
