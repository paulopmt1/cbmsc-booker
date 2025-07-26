import React from 'react';
import PersonItem from './PersonItem';

const PeriodSection = ({ title, people, selectedPeople, onPersonSelect, disabled = false }) => {
    return (
        <div className="period-section">
            <h2 className="period-title">{title}</h2>
            <div className="people-list">
                {people.map(person => (
                    <PersonItem
                        key={person.id}
                        person={person}
                        isSelected={selectedPeople.includes(person.id)}
                        onSelect={() => onPersonSelect(person.id)}
                        disabled={disabled}
                    />
                ))}
            </div>
        </div>
    );
};

export default PeriodSection; 