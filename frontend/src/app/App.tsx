import { ThemeProvider } from './components/ThemeProvider';
import { SessionProvider } from './context/SessionContext';
import { Home } from './Home';

const App = () => {
  return (
    <ThemeProvider>
      <SessionProvider>
        <Home />
      </SessionProvider>
    </ThemeProvider>
  );
};

export default App;