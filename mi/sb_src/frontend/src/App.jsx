import { BrowserRouter as Router, Routes, Route, Link, useNavigate } from 'react-router-dom';
import Login from './components/Login';
import PostCreator from './components/PostCreator';
import UserManagement from './components/UserManagement';

const LandingPage = () => (
  <div className="min-h-screen bg-slate-900 text-white flex flex-col items-center justify-center p-4">
    <div className="max-w-4xl w-full space-y-8 text-center animate-in fade-in duration-1000">
      <div className="space-y-4">
        <h1 className="text-6xl font-extrabold tracking-tight bg-gradient-to-r from-green-400 to-emerald-600 bg-clip-text text-transparent">
          StoryBlast MI RaRa
        </h1>
        <p className="text-xl text-slate-400 max-w-2xl mx-auto">
          Platform automasi konten dan publikasi media sosial untuk MI Raden Rahmat Balongbendo. 
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="p-6 bg-slate-800 rounded-2xl border border-slate-700">
          <span className="text-2xl">🚀</span>
          <h3 className="text-lg font-bold">Auto Post</h3>
        </div>
        <div className="p-6 bg-slate-800 rounded-2xl border border-slate-700">
          <span className="text-2xl">🤖</span>
          <h3 className="text-lg font-bold">AI Caption</h3>
        </div>
        <div className="p-6 bg-slate-800 rounded-2xl border border-slate-700">
          <span className="text-2xl">📊</span>
          <h3 className="text-lg font-bold">Real-time Monitor</h3>
        </div>
      </div>

      <div className="pt-8 flex flex-col items-center gap-4">
        <Link 
          to="/login" 
          className="px-10 py-4 bg-brand-green hover:bg-emerald-600 text-white font-bold rounded-full shadow-lg shadow-emerald-500/20 transition-all active:scale-95"
        >
          Masuk ke Dashboard
        </Link>
      </div>
    </div>
  </div>
);

const Dashboard = ({ active = 'post' }) => {
  const navigate = useNavigate();
  const user = JSON.parse(localStorage.getItem('storyblast_user') || '{}');

  if (!localStorage.getItem('storyblast_token')) {
    navigate('/login');
    return null;
  }

  return (
    <div className="min-h-screen bg-slate-950 text-white flex">
      {/* Sidebar */}
      <aside className="w-64 bg-slate-900 border-r border-slate-800 p-6 space-y-8 hidden md:block">
        <div className="text-2xl font-black text-brand-green">StoryBlast</div>
        <nav className="space-y-2">
          <Link to="/dashboard" className={`w-full flex items-center px-4 py-3 rounded-xl font-medium ${active === 'post' ? 'bg-brand-green text-white' : 'text-slate-400 hover:bg-slate-800'}`}>
             ✍️ Create Story
          </Link>
          <button className="w-full text-left px-4 py-3 rounded-xl font-medium text-slate-400 hover:bg-slate-800">
             📋 Post Logs
          </button>
          <Link to="/user-management" className={`w-full flex items-center px-4 py-3 rounded-xl font-medium ${active === 'users' ? 'bg-brand-green text-white' : 'text-slate-400 hover:bg-slate-800'}`}>
             👤 Users
          </Link>
        </nav>
        
        <div className="pt-20">
           <div className="bg-slate-800/50 p-4 rounded-2xl border border-slate-700">
              <p className="text-xs text-slate-500 uppercase font-bold tracking-widest">Signed in as</p>
              <p className="text-sm font-bold text-white mt-1">{user.username}</p>
              <button 
                onClick={() => { localStorage.clear(); navigate('/login'); }} 
                className="mt-4 text-xs text-red-500 font-bold hover:underline"
              >
                Logout
              </button>
           </div>
        </div>
      </aside>

      {/* Main Content */}
      <main className="flex-1 overflow-y-auto bg-slate-950">
        <div className="p-8">
           {active === 'post' ? <PostCreator /> : <UserManagement />}
        </div>
      </main>
    </div>
  );
};

function App() {
  return (
    <Router basename="/storyblast">
      <Routes>
        <Route path="/" element={<LandingPage />} />
        <Route path="/login" element={<Login />} />
        <Route path="/dashboard" element={<Dashboard />} />
        <Route path="/user-management" element={<Dashboard key="admin" active="users" />} />
      </Routes>
    </Router>
  )
}

export default App
