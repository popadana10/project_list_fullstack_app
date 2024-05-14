import './App.css';
import ProjectList from './components/ProjectList';
import AddProject from './components/AddProject';
import { BrowserRouter as Router, Route, Routes } from 'react-router-dom';

const App = () => {
  return (
    <Router>
      <Routes>
        <Route path="/projects" element={<ProjectList />} />
        <Route path="/" element={<AddProject />} />
        {/* ... other routes */}
      </Routes>
    </Router>
  );
};

export default App;