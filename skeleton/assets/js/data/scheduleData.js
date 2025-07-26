// Sample data for schedule conflicts across 30 days
export const generateRandomScheduleData = () => {
    const days = [];
    const peopleNames = [
        'BC Roberto', 'BC Ana', 'BC Fábio', 'BC Léo', 'BC Aline',
        'BC Maria', 'BC João', 'BC Carla', 'BC Pedro', 'BC Sofia',
        'BC Lucas', 'BC Julia', 'BC Rafael', 'BC Beatriz', 'BC Thiago',
        'BC Camila', 'BC Diego', 'BC Fernanda', 'BC André', 'BC Isabela'
    ];

    for (let day = 1; day <= 30; day++) {
        const hasConflict = Math.random() > 0.3; // 70% chance of having conflicts
        
        if (hasConflict) {
            const timePeriods = [
                {
                    id: 'integral',
                    title: 'Período Integral',
                    people: generateRandomPeople(peopleNames, 2, 4)
                },
                {
                    id: 'noturno',
                    title: 'Período Noturno',
                    people: generateRandomPeople(peopleNames, 1, 3)
                },
                {
                    id: 'diurno',
                    title: 'Período Diurno',
                    people: generateRandomPeople(peopleNames, 2, 4)
                }
            ];

            days.push({
                day: day,
                date: new Date(2024, 3, day), // April 2024
                hasConflict: true,
                timePeriods: timePeriods,
                resolved: false,
                resolvedBy: null,
                resolvedAt: null
            });
        } else {
            days.push({
                day: day,
                date: new Date(2024, 3, day),
                hasConflict: false,
                timePeriods: [],
                resolved: false,
                resolvedBy: null,
                resolvedAt: null
            });
        }
    }

    return days;
};

const generateRandomPeople = (allPeople, minCount, maxCount) => {
    const count = Math.floor(Math.random() * (maxCount - minCount + 1)) + minCount;
    const shuffled = [...allPeople].sort(() => 0.5 - Math.random());
    return shuffled.slice(0, count).map((name, index) => ({
        id: `${name.toLowerCase().replace(/\s+/g, '_')}_${index}`,
        name: name
    }));
};

// Get schedule data for a specific day
export const getScheduleForDay = (day) => {
    const allData = generateRandomScheduleData();
    return allData.find(d => d.day === day) || null;
};

// Get all schedule data
export const getAllScheduleData = () => {
    return generateRandomScheduleData();
};

// Get conflicts for a specific date range
export const getConflictsForDateRange = (startDate, endDate) => {
    const allData = generateRandomScheduleData();
    return allData.filter(day => {
        const dayDate = day.date;
        return dayDate >= startDate && dayDate <= endDate && day.hasConflict;
    });
};

// Get unresolved conflicts
export const getUnresolvedConflicts = () => {
    const allData = generateRandomScheduleData();
    return allData.filter(day => day.hasConflict && !day.resolved);
};

// Save resolution for a specific day
export const saveResolution = (day, selectedPeople, resolvedBy = 'User') => {
    const resolution = {
        day: day,
        selectedPeople: selectedPeople,
        resolvedBy: resolvedBy,
        resolvedAt: new Date(),
        resolved: true
    };
    
    // In a real application, this would save to a database
    // For now, we'll store in localStorage
    const resolutions = JSON.parse(localStorage.getItem('scheduleResolutions') || '{}');
    resolutions[day] = resolution;
    localStorage.setItem('scheduleResolutions', JSON.stringify(resolutions));
    
    return resolution;
};

// Get resolution for a specific day
export const getResolution = (day) => {
    const resolutions = JSON.parse(localStorage.getItem('scheduleResolutions') || '{}');
    return resolutions[day] || null;
};

// Get all resolutions
export const getAllResolutions = () => {
    const resolutions = JSON.parse(localStorage.getItem('scheduleResolutions') || '{}');
    return resolutions;
};

// Clear all resolutions (for testing)
export const clearAllResolutions = () => {
    localStorage.removeItem('scheduleResolutions');
}; 