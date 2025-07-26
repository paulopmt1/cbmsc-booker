# Schedule Management API Endpoints

This document describes the API endpoints for the schedule conflict management system.

## Base URL
All endpoints are prefixed with `/api/schedule`

## Endpoints

### 1. Get All Schedule Data
- **URL**: `GET /api/schedule/all`
- **Description**: Retrieves all schedule data for the 30-day period
- **Response**: 
```json
{
    "success": true,
    "data": [
        {
            "day": 1,
            "date": "2024-04-01",
            "hasConflict": true,
            "timePeriods": [...],
            "resolved": false,
            "resolvedBy": null,
            "resolvedAt": null
        }
    ]
}
```

### 2. Get Schedule for Specific Day
- **URL**: `GET /api/schedule/day/{day}`
- **Parameters**: `day` (integer, 1-30)
- **Description**: Retrieves schedule data for a specific day
- **Response**: 
```json
{
    "success": true,
    "data": {
        "day": 15,
        "date": "2024-04-15",
        "hasConflict": true,
        "timePeriods": [...],
        "resolved": false,
        "resolvedBy": null,
        "resolvedAt": null
    }
}
```

### 3. Get Unresolved Conflicts
- **URL**: `GET /api/schedule/unresolved`
- **Description**: Retrieves all days with unresolved conflicts
- **Response**: 
```json
{
    "success": true,
    "data": [
        {
            "day": 15,
            "date": "2024-04-15",
            "hasConflict": true,
            "timePeriods": [...],
            "resolved": false
        }
    ]
}
```

### 4. Get Resolution for Day
- **URL**: `GET /api/schedule/resolution/{day}`
- **Parameters**: `day` (integer, 1-30)
- **Description**: Retrieves resolution data for a specific day
- **Response**: 
```json
{
    "success": true,
    "data": {
        "day": 15,
        "selectedPeople": ["roberto_0", "ana_1"],
        "resolvedBy": "User",
        "resolvedAt": "2024-04-15 10:30:00",
        "resolved": true
    }
}
```

### 5. Save Resolution
- **URL**: `POST /api/schedule/resolution/{day}`
- **Parameters**: `day` (integer, 1-30)
- **Body**: 
```json
{
    "selectedPeople": ["roberto_0", "ana_1"],
    "resolvedBy": "User"
}
```
- **Description**: Saves resolution for a specific day
- **Response**: 
```json
{
    "success": true,
    "data": {
        "day": 15,
        "selectedPeople": ["roberto_0", "ana_1"],
        "resolvedBy": "User",
        "resolvedAt": "2024-04-15 10:30:00",
        "resolved": true
    },
    "message": "Resolution saved successfully"
}
```

### 6. Get All Resolutions
- **URL**: `GET /api/schedule/resolutions`
- **Description**: Retrieves all saved resolutions
- **Response**: 
```json
{
    "success": true,
    "data": {
        "15": {
            "day": 15,
            "selectedPeople": ["roberto_0", "ana_1"],
            "resolvedBy": "User",
            "resolvedAt": "2024-04-15 10:30:00",
            "resolved": true
        }
    }
}
```

### 7. Clear All Resolutions
- **URL**: `DELETE /api/schedule/resolutions/clear`
- **Description**: Clears all saved resolutions
- **Response**: 
```json
{
    "success": true,
    "message": "All resolutions cleared successfully"
}
```

### 8. Regenerate Data
- **URL**: `POST /api/schedule/regenerate`
- **Description**: Regenerates random schedule data
- **Response**: 
```json
{
    "success": true,
    "data": [...],
    "message": "Schedule data regenerated successfully"
}
```

## Data Storage

The system uses file-based storage:
- Schedule data: `var/schedule_data.json`
- Resolutions: `var/schedule_resolutions.json`

## Error Responses

All endpoints return error responses in the following format:
```json
{
    "success": false,
    "error": "Error message description"
}
```

## Frontend Integration

The frontend React components use the `ApiService` class to communicate with these endpoints. The service is available globally as `window.ApiService`.

## Usage Example

```javascript
const apiService = new window.ApiService();

// Get all schedule data
const scheduleData = await apiService.getAllScheduleData();

// Save a resolution
const resolution = await apiService.saveResolution(15, ['roberto_0', 'ana_1']);
``` 