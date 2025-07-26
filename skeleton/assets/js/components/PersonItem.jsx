import React from 'react';

const PersonItem = ({ person, isSelected, onSelect, disabled = false }) => {
    return (
        <div className="person-item">
            <label className={`person-label ${disabled ? 'disabled' : ''}`}>
                <input
                    type="checkbox"
                    className="person-checkbox"
                    checked={isSelected}
                    onChange={onSelect}
                    disabled={disabled}
                />
                <span className="person-name">{person.name}</span>
            </label>
        </div>
    );
};

export default PersonItem; 