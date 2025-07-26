import React, { useState } from 'react';
import PeriodSection from './PeriodSection';

const ConflictManagement = () => {
    const [selectedPeople, setSelectedPeople] = useState([]);

    const handlePersonSelect = (personId) => {
        setSelectedPeople(prev => {
            if (prev.includes(personId)) {
                return prev.filter(id => id !== personId);
            } else {
                return [...prev, personId];
            }
        });
    };

    const handleConfirm = () => {
        console.log('Selected people:', selectedPeople);
        // Here you would typically send the data to your backend
        alert('Conflito resolvido com sucesso!');
    };

    const fullTimePeople = [
        { id: 'roberto', name: 'BC Roberto' },
        { id: 'ana', name: 'BC Ana' },
        { id: 'fabio', name: 'BC Fábio' }
    ];

    const nightTimePeople = [
        { id: 'leo', name: 'BC Léo' },
        { id: 'aline', name: 'BC Aline' }
    ];

    return (
        <div className="conflict-management">
            <div className="conflict-card">
                <h1 className="conflict-title">Gestão de conflitos de horários</h1>
                <p className="conflict-subtitle">Conflito Terça-feira 15/04</p>
                
                <div className="periods-container">
                    <PeriodSection 
                        title="Período Integral"
                        people={fullTimePeople}
                        selectedPeople={selectedPeople}
                        onPersonSelect={handlePersonSelect}
                    />
                    
                    <PeriodSection 
                        title="Período Noturno"
                        people={nightTimePeople}
                        selectedPeople={selectedPeople}
                        onPersonSelect={handlePersonSelect}
                    />
                </div>
                
                <button 
                    className="confirm-button"
                    onClick={handleConfirm}
                >
                    Confirmar e salvar
                </button>
            </div>
        </div>
    );
};

export default ConflictManagement; 