import React, { useState, useEffect } from 'react';
import PeriodSection from './PeriodSection';
import { getScheduleForDay, saveResolution, getResolution } from '../data/scheduleData';

const ConflictManagement = ({ selectedDay, onBackToCalendar }) => {
    const [selectedPeople, setSelectedPeople] = useState([]);
    const [validationError, setValidationError] = useState('');
    const [dayData, setDayData] = useState(null);
    const [isResolved, setIsResolved] = useState(false);

    useEffect(() => {
        if (selectedDay) {
            const data = getScheduleForDay(selectedDay);
            setDayData(data);
            
            // Check if this day is already resolved
            const resolution = getResolution(selectedDay);
            if (resolution) {
                setIsResolved(true);
                setSelectedPeople(resolution.selectedPeople);
            } else {
                setIsResolved(false);
                setSelectedPeople([]);
            }
        }
    }, [selectedDay]);

    const handlePersonSelect = (personId) => {
        if (isResolved) return; // Prevent changes if already resolved
        
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
        if (!dayData || !dayData.hasConflict) return true;

        // Check if at least one person is selected from each period
        const unselectedPeriods = dayData.timePeriods.filter(period => {
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

        // Save the resolution
        const resolution = saveResolution(selectedDay, selectedPeople);
        setIsResolved(true);
        
        console.log('Resolution saved:', resolution);
        alert('Conflito resolvido com sucesso!');
    };

    const formatDate = (date) => {
        const daysOfWeek = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
        const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        
        return `${daysOfWeek[date.getDay()]} ${date.getDate()} de ${months[date.getMonth()]}`;
    };

    if (!dayData) {
        return (
            <div className="conflict-management">
                <div className="conflict-card">
                    <p>Selecione um dia no calendário para gerenciar conflitos.</p>
                </div>
            </div>
        );
    }

    if (!dayData.hasConflict) {
        return (
            <div className="conflict-management">
                <div className="conflict-card">
                    <h1 className="conflict-title">Gestão de conflitos de horários</h1>
                    <p className="conflict-subtitle">{formatDate(dayData.date)}</p>
                    <div className="no-conflict-message">
                        <p>Não há conflitos de horário para este dia.</p>
                    </div>
                    <button 
                        className="back-button"
                        onClick={onBackToCalendar}
                    >
                        Voltar ao calendário
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="conflict-management">
            <div className="conflict-card">
                <h1 className="conflict-title">Gestão de conflitos de horários</h1>
                <p className="conflict-subtitle">{formatDate(dayData.date)}</p>
                
                {isResolved && (
                    <div className="resolved-notice">
                        ✅ Este conflito já foi resolvido
                    </div>
                )}
                
                {validationError && (
                    <div className="validation-error">
                        {validationError}
                    </div>
                )}
                
                <div className="periods-container">
                    {dayData.timePeriods.map(period => (
                        <PeriodSection 
                            key={period.id}
                            title={period.title}
                            people={period.people}
                            selectedPeople={selectedPeople}
                            onPersonSelect={handlePersonSelect}
                            disabled={isResolved}
                        />
                    ))}
                </div>
                
                <div className="button-container">
                    <button 
                        className="back-button"
                        onClick={onBackToCalendar}
                    >
                        Voltar ao calendário
                    </button>
                    
                    {!isResolved && (
                        <button 
                            className="confirm-button"
                            onClick={handleConfirm}
                        >
                            Confirmar e salvar
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ConflictManagement; 