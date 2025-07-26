import React, { useState, useEffect } from 'react';
import PeriodSection from './PeriodSection';

const ConflictManagement = ({ selectedDay, onBackToCalendar }) => {
    const [selectedPeople, setSelectedPeople] = useState([]);
    const [validationError, setValidationError] = useState('');
    const [dayData, setDayData] = useState(null);
    const [isResolved, setIsResolved] = useState(false);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (selectedDay) {
            loadDayData();
        }
    }, [selectedDay]);

    const loadDayData = async () => {
        try {
            setLoading(true);
            setError(null);
            
            // Use global ApiService or fallback to window.ApiService
            const ApiService = window.ApiService;
            if (!ApiService) {
                throw new Error('ApiService not available');
            }
            
            const apiService = new ApiService();
            const [scheduleData, resolutionData] = await Promise.all([
                apiService.getScheduleForDay(selectedDay),
                apiService.getResolution(selectedDay)
            ]);
            
            setDayData(scheduleData);
            
            if (resolutionData) {
                setIsResolved(true);
                setSelectedPeople(resolutionData.selectedPeople || []);
            } else {
                setIsResolved(false);
                setSelectedPeople([]);
            }
        } catch (err) {
            console.error('Failed to load day data:', err);
            setError('Erro ao carregar dados do dia selecionado');
        } finally {
            setLoading(false);
        }
    };

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

    const handleConfirm = async () => {
        if (!validateSelections()) {
            return;
        }

        try {
            const ApiService = window.ApiService;
            if (!ApiService) {
                throw new Error('ApiService not available');
            }
            
            const apiService = new ApiService();
            const resolution = await apiService.saveResolution(selectedDay, selectedPeople);
            setIsResolved(true);
            
            console.log('Resolution saved:', resolution);
            alert('Conflito resolvido com sucesso!');
        } catch (err) {
            console.error('Failed to save resolution:', err);
            alert('Erro ao salvar resolução. Tente novamente.');
        }
    };

    const formatDate = (dateString) => {
        const date = new Date(dateString);
        const daysOfWeek = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
        const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        
        return `${daysOfWeek[date.getDay()]} ${date.getDate()} de ${months[date.getMonth()]}`;
    };

    if (loading) {
        return (
            <div className="conflict-management">
                <div className="conflict-card">
                    <div className="loading-message">
                        <p>Carregando dados do conflito...</p>
                    </div>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="conflict-management">
                <div className="conflict-card">
                    <div className="error-message">
                        <p>{error}</p>
                        <button onClick={loadDayData} className="retry-button">
                            Tentar novamente
                        </button>
                    </div>
                </div>
            </div>
        );
    }

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