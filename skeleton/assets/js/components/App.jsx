import React from 'react';
import * as ReactDOM from 'react-dom/client';
import ConflictManagement from './ConflictManagement';
import '../../styles/conflict-management.css';

class App extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            // State can be added here if needed
        };
    }

    render() {
        return (
            <div>
                <ConflictManagement />
            </div>
        );
    }
}

export default App;

// Using the new React 18 createRoot API
const rootElement = document.getElementById('root');
if (rootElement) {
    const root = ReactDOM.createRoot(rootElement);
    root.render(<App />);
}