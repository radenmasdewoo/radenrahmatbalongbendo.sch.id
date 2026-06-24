const API_BASE = import.meta.env.PROD 
  ? '/api/storyblast' 
  : 'http://localhost:5000/api';

export default API_BASE;
