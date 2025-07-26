import React from 'react';

const PersonItem = ({ person, isSelected, onSelect }) => {
    return (
        <div className="person-item">
            <label className="person-label">
                <input
                    type="checkbox"
                    className="person-checkbox"
                    checked={isSelected}
                    onChange={onSelect}
                />
                <span className="person-name">{person.name}</span>
            </label>
        </div>
    );
};

export default PersonItem; 