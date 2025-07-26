import React, { useState, useEffect } from 'react';

const CalendarView = ({ onDaySelect, selectedDay }) => {
    const [scheduleData, setScheduleData] = useState([]);
    const [resolutions, setResolutions] = useState({});
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        try {
            setLoading(true);
            setError(null);
            
            // Use global ApiService or fallback to window.ApiService
            const ApiService = window.ApiService;
            if (!ApiService) {
                throw new Error('ApiService not available');
            }
            
            const apiService = new ApiService();
            const [scheduleResponse, resolutionsResponse] = await Promise.all([
                apiService.getAllScheduleData(),
                apiService.getAllResolutions()
            ]);
            
            setScheduleData(scheduleResponse);
            setResolutions(resolutionsResponse);
        } catch (err) {
            console.error('Failed to load data:', err);
            setError('Erro ao carregar dados do calendário');
        } finally {
            setLoading(false);
        }
    };

    const formatDate = (dateString) => {
        const date = new Date(dateString);
        const daysOfWeek = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        
        return `${daysOfWeek[date.getDay()]} ${date.getDate()}/${months[date.getMonth()]}`;
    };

    const getDayStatus = (day) => {
        if (!day.hasConflict) {
            return 'no-conflict';
        }
        
        if (resolutions[day.day]) {
            return 'resolved';
        }
        
        return 'unresolved';
    };

    const getStatusText = (status) => {
        switch (status) {
            case 'no-conflict':
                return 'Sem conflito';
            case 'resolved':
                return 'Resolvido';
            case 'unresolved':
                return 'Pendente';
            default:
                return '';
        }
    };

    const getStatusClass = (status) => {
        switch (status) {
            case 'no-conflict':
                return 'day-no-conflict';
            case 'resolved':
                return 'day-resolved';
            case 'unresolved':
                return 'day-unresolved';
            default:
                return '';
        }
    };

    if (loading) {
        return (
            <div className="calendar-view">
                <div className="loading-message">
                    <p>Carregando calendário...</p>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="calendar-view">
                <div className="error-message">
                    <p>{error}</p>
                    <button onClick={loadData} className="retry-button">
                        Tentar novamente
                    </button>
                </div>
            </div>
        );
    }

    return (
        <div className="calendar-view">
            <h2 className="calendar-title">Calendário de Conflitos - Abril 2024</h2>
            <div className="calendar-grid">
                {scheduleData.map(day => {
                    const status = getDayStatus(day);
                    const isSelected = selectedDay === day.day;
                    
                    return (
                        <div 
                            key={day.day}
                            className={`calendar-day ${getStatusClass(status)} ${isSelected ? 'selected' : ''} ${day.hasConflict ? 'clickable' : ''}`}
                            onClick={() => day.hasConflict && onDaySelect(day.day)}
                        >
                            <div className="day-number">{day.day}</div>
                            <div className="day-date">{formatDate(day.date)}</div>
                            {day.hasConflict && (
                                <div className="day-status">
                                    {getStatusText(status)}
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>
            
            <div className="calendar-legend">
                <div className="legend-item">
                    <div className="legend-color no-conflict"></div>
                    <span>Sem conflito</span>
                </div>
                <div className="legend-item">
                    <div className="legend-color unresolved"></div>
                    <span>Conflito pendente</span>
                </div>
                <div className="legend-item">
                    <div className="legend-color resolved"></div>
                    <span>Conflito resolvido</span>
                </div>
            </div>
        </div>
    );
};

export default CalendarView; 