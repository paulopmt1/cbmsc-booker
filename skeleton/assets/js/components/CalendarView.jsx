import React, { useState, useEffect } from 'react';
import { getAllScheduleData, getResolution } from '../data/scheduleData';

const CalendarView = ({ onDaySelect, selectedDay }) => {
    const [scheduleData, setScheduleData] = useState([]);
    const [resolutions, setResolutions] = useState({});

    useEffect(() => {
        const data = getAllScheduleData();
        setScheduleData(data);
        
        // Load resolutions from localStorage
        const storedResolutions = JSON.parse(localStorage.getItem('scheduleResolutions') || '{}');
        setResolutions(storedResolutions);
    }, []);

    const formatDate = (date) => {
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