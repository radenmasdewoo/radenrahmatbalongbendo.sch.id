import React, { useState } from 'react';
import API_BASE from '../apiConfig';

const PostCreator = () => {
  const [caption, setCaption] = useState('');
  const [media, setMedia] = useState(null);
  const [selectedPlatforms, setSelectedPlatforms] = useState([]);
  const [isPosting, setIsPosting] = useState(false);
  const [results, setResults] = useState(null);

  const platforms = [
    { id: 'facebook', name: 'Facebook', icon: '🔵' },
    { id: 'instagram', name: 'Instagram', icon: '📸' },
    { id: 'whatsapp', name: 'WhatsApp', icon: '🟢' }
  ];

  const handlePlatformToggle = (platformId) => {
    setSelectedPlatforms(prev => 
      prev.includes(platformId) 
        ? prev.filter(p => p !== platformId) 
        : [...prev, platformId]
    );
  };

  const handleFileChange = (e) => {
    if (e.target.files && e.target.files[0]) {
      setMedia(e.target.files[0]);
    }
  };

  const handlePost = async () => {
    if (!caption || selectedPlatforms.length === 0) {
      alert('Tolong isi caption dan pilih minimal satu platform.');
      return;
    }

    setIsPosting(true);
    setResults(null);

    const formData = new FormData();
    formData.append('caption', caption);
    formData.append('platforms', JSON.stringify(selectedPlatforms));
    if (media) formData.append('media', media);

    try {
      const token = localStorage.getItem('storyblast_token');
      const response = await fetch(`${API_BASE}/post/story`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`
        },
        body: formData
      });

      const data = await response.json();
      setResults(data.results);
      if (response.ok) {
        setCaption('');
        setMedia(null);
        setSelectedPlatforms([]);
      }
    } catch (error) {
      console.error('Post error:', error);
      alert('Gagal mengirim postingan.');
    } finally {
      setIsPosting(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto p-6 space-y-8 animate-in slide-in-from-bottom-4 duration-500">
      <header className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-extrabold text-white flex items-center gap-3">
            <span className="p-2 bg-brand-green/20 rounded-xl">✨</span>
            Buat Story Baru
          </h1>
          <p className="text-slate-400 mt-1">Publikasi serentak ke berbagai platform media sosial.</p>
        </div>
      </header>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Main Form */}
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
            <label className="block text-sm font-semibold text-slate-300">Caption Cerita</label>
            <textarea
              className="w-full h-40 bg-slate-800 border-none rounded-xl p-4 text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-green outline-none transition-all resize-none"
              placeholder="Tulis caption menarik di sini..."
              value={caption}
              onChange={(e) => setCaption(e.target.value)}
            />
            <div className="flex justify-between items-center text-xs text-slate-500 px-1">
              <span>Mendukung emoji 🕌 ✨</span>
              <span>{caption.length} karakter</span>
            </div>
          </div>

          <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
            <label className="block text-sm font-semibold text-slate-300">Media (Gambar/Video)</label>
            <div className={`relative border-2 border-dashed rounded-2xl p-8 transition-all flex flex-col items-center justify-center gap-3 ${media ? 'border-brand-green bg-brand-green/5' : 'border-slate-700 hover:border-slate-500'}`}>
              <input 
                type="file" 
                className="absolute inset-0 opacity-0 cursor-pointer" 
                onChange={handleFileChange}
                accept="image/*,video/*"
              />
              {media ? (
                <div className="text-center">
                  <p className="text-brand-green font-bold">📄 {media.name}</p>
                  <p className="text-xs text-slate-500 mt-1">{(media.size / 1024 / 1024).toFixed(2)} MB</p>
                  <button onClick={(e) => { e.stopPropagation(); setMedia(null); }} className="mt-2 text-xs text-red-400 hover:underline">Hapus file</button>
                </div>
              ) : (
                <>
                  <div className="w-12 h-12 bg-slate-800 rounded-full flex items-center justify-center text-2xl">📤</div>
                  <div className="text-center">
                    <p className="text-slate-300 font-medium">Klik atau drop media di sini</p>
                    <p className="text-xs text-slate-500 mt-1">Image/Video up to 50MB</p>
                  </div>
                </>
              )}
            </div>
          </div>
        </div>

        {/* Sidebar & Settings */}
        <div className="space-y-6">
          <div className="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
            <label className="block text-sm font-semibold text-slate-300">Pilih Platform</label>
            <div className="space-y-3">
              {platforms.map(p => (
                <button
                  key={p.id}
                  onClick={() => handlePlatformToggle(p.id)}
                  className={`w-full flex items-center justify-between p-4 rounded-xl transition-all border ${selectedPlatforms.includes(p.id) ? 'bg-brand-green/10 border-brand-green text-white' : 'bg-slate-800 border-transparent text-slate-400 hover:bg-slate-700'}`}
                >
                  <div className="flex items-center gap-3 font-semibold">
                    <span>{p.icon}</span>
                    {p.name}
                  </div>
                  {selectedPlatforms.includes(p.id) && <span className="text-brand-green">✓</span>}
                </button>
              ))}
            </div>
          </div>

          <button
            onClick={handlePost}
            disabled={isPosting}
            className={`w-full py-4 rounded-xl font-bold text-white shadow-xl shadow-brand-green/20 transition-all active:scale-95 ${isPosting ? 'bg-slate-700 cursor-not-allowed' : 'bg-brand-green hover:bg-emerald-600'}`}
          >
            {isPosting ? 'Sedang Memproses...' : '🚀 Post Sekarang'}
          </button>

          {results && (
            <div className="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-3 animate-in fade-in duration-300">
              <h4 className="text-xs font-bold text-slate-500 uppercase tracking-wider">Status Terakhir</h4>
              {results.map((res, i) => (
                <div key={i} className="flex items-center justify-between bg-slate-800/50 p-3 rounded-lg border border-slate-700">
                  <span className="text-sm font-medium capitalize flex items-center gap-2">
                    {res.platform === 'facebook' ? '🔵' : res.platform === 'instagram' ? '📸' : '🟢'}
                    {res.platform}
                  </span>
                  <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${res.status === 'success' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500'}`}>
                    {res.status === 'success' ? 'BERHASIL' : 'GAGAL'}
                  </span>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default PostCreator;
