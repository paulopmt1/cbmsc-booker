import React, { useState } from 'react';
import * as ReactDOM from 'react-dom/client';
import CalendarView from './CalendarView';
import ConflictManagement from './ConflictManagement';
import '../../styles/conflict-management.css';

class App extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            selectedDay: null,
            view: 'calendar' // 'calendar' or 'conflict'
        };
    }

    handleDaySelect = (day) => {
        this.setState({
            selectedDay: day,
            view: 'conflict'
        });
    };

    handleBackToCalendar = () => {
        this.setState({
            selectedDay: null,
            view: 'calendar'
        });
    };

    render() {
        const { view, selectedDay } = this.state;

        return (
            <div>
                {view === 'calendar' ? (
                    <CalendarView 
                        onDaySelect={this.handleDaySelect}
                        selectedDay={selectedDay}
                    />
                ) : (
                    <ConflictManagement 
                        selectedDay={selectedDay}
                        onBackToCalendar={this.handleBackToCalendar}
                    />
                )}
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