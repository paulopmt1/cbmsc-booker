import React, { useState } from 'react';
import PeriodSection from './PeriodSection';

const ConflictManagement = () => {
    const [selectedPeople, setSelectedPeople] = useState([]);
    const [validationError, setValidationError] = useState('');

    const handlePersonSelect = (personId) => {
        setSelectedPeople(prev => {
            if (prev.includes(personId)) {
                return prev.filter(id => id !== personId);
            } else {
                return [...prev, personId];
            }
        });
        // Clear validation error when user makes a selection
        if (validationError) {
            setValidationError('');
        }
    };

    const validateSelections = () => {
        const timePeriods = [
            {
                id: 'integral',
                title: 'Período Integral',
                people: [
                    { id: 'roberto', name: 'BC Roberto' },
                    { id: 'ana', name: 'BC Ana' },
                    { id: 'fabio', name: 'BC Fábio' }
                ]
            },
            {
                id: 'noturno',
                title: 'Período Noturno',
                people: [
                    { id: 'leo', name: 'BC Léo' },
                    { id: 'aline', name: 'BC Aline' }
                ]
            },
            {
                id: 'diurno',
                title: 'Período Diurno',
                people: [
                    { id: 'maria', name: 'BC Maria' },
                    { id: 'joao', name: 'BC João' },
                    { id: 'carla', name: 'BC Carla' }
                ]
            }
        ];

        // Check if at least one person is selected from each period
        const unselectedPeriods = timePeriods.filter(period => {
            const periodPeopleIds = period.people.map(person => person.id);
            const hasSelection = selectedPeople.some(personId => 
                periodPeopleIds.includes(personId)
            );
            return !hasSelection;
        });

        if (unselectedPeriods.length > 0) {
            const periodNames = unselectedPeriods.map(period => period.title).join(', ');
            setValidationError(`É obrigatório selecionar pelo menos uma pessoa de cada período. Períodos sem seleção: ${periodNames}`);
            return false;
        }

        return true;
    };

    const handleConfirm = () => {
        if (!validateSelections()) {
            return;
        }

        console.log('Selected people:', selectedPeople);
        // Here you would typically send the data to your backend
        alert('Conflito resolvido com sucesso!');
    };

    const timePeriods = [
        {
            id: 'integral',
            title: 'Período Integral',
            people: [
                { id: 'roberto', name: 'BC Roberto' },
                { id: 'ana', name: 'BC Ana' },
                { id: 'fabio', name: 'BC Fábio' }
            ]
        },
        {
            id: 'noturno',
            title: 'Período Noturno',
            people: [
                { id: 'leo', name: 'BC Léo' },
                { id: 'aline', name: 'BC Aline' }
            ]
        },
        {
            id: 'diurno',
            title: 'Período Diurno',
            people: [
                { id: 'maria', name: 'BC Maria' },
                { id: 'joao', name: 'BC João' },
                { id: 'carla', name: 'BC Carla' }
            ]
        }
    ];

    return (
        <div className="conflict-management">
            <div className="conflict-card">
                <h1 className="conflict-title">Gestão de conflitos de horários</h1>
                <p className="conflict-subtitle">Conflito Terça-feira 15/04</p>
                
                {validationError && (
                    <div className="validation-error">
                        {validationError}
                    </div>
                )}
                
                <div className="periods-container">
                    {timePeriods.map(period => (
                        <PeriodSection 
                            key={period.id}
                            title={period.title}
                            people={period.people}
                            selectedPeople={selectedPeople}
                            onPersonSelect={handlePersonSelect}
                        />
                    ))}
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