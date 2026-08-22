import React from 'react';
import * as ReactDOM from 'react-dom/client';

class App extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            message: 'Hello from React!'
        };
    }

    render() {
        return (
            <div>
                <h1>{this.state.message}</h1>
                <p>React is now integrated with Symfony!</p>
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