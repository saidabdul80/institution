# Staff Course Management System

## Overview

The Staff Course Management System provides comprehensive functionality for assigning courses to staff members with different roles and responsibilities. This system supports the academic workflow by managing lecturer assignments, course coordinators, and external examiners.

## Key Features

### 1. Multi-Role Course Allocation
- **Lecturer**: Primary instructor for the course
- **Course Coordinator**: Oversees course delivery and assessment
- **External Examiner**: Reviews and validates assessment processes

### 2. Comprehensive Assignment Management
- **Session-based Allocations**: Assignments tied to specific academic sessions
- **Semester-specific**: Separate allocations for different semesters
- **Programme and Level Context**: Allocations specific to academic programmes and levels
- **Status Management**: Active/inactive allocation control

### 3. Advanced Filtering and Search
- **Multi-criteria Filtering**: Filter by session, semester, staff, allocation type
- **Search Functionality**: Search staff by name or ID, courses by code or title
- **Department/Faculty Filtering**: Organize by academic units

### 4. Bulk Operations
- **Bulk Allocation Creation**: Create multiple allocations simultaneously
- **Copy from Previous Sessions**: Replicate successful allocation patterns
- **Batch Status Updates**: Activate/deactivate multiple allocations

## Database Schema

### Staff Course Allocations Table
```sql
staff_course_allocations:
- id (Primary Key)
- staff_id (Foreign Key to staff)
- course_id (Foreign Key to courses)
- session_id (Foreign Key to sessions)
- semester (Integer: 1, 2, 3)
- programme_id (Foreign Key to programmes)
- level_id (Foreign Key to levels)
- allocation_type (Enum: lecturer, coordinator, examiner)
- remarks (Text: Optional notes)
- is_active (Boolean: Status flag)
- allocated_by (Foreign Key to staff who made allocation)
- allocated_at (Timestamp)
- created_at, updated_at (Timestamps)
```

### Key Relationships
- **Staff → Allocations**: One-to-Many (Staff can have multiple course allocations)
- **Course → Allocations**: One-to-Many (Course can have multiple staff allocations)
- **Session → Allocations**: One-to-Many (Session contains multiple allocations)
- **Programme → Allocations**: One-to-Many (Programme has multiple course allocations)
- **Level → Allocations**: One-to-Many (Level has multiple course allocations)

## API Endpoints

### Core Allocation Management
- `GET /api/staff/staff-course-allocations` - Get course allocations with filtering
- `POST /api/staff/staff-course-allocations` - Create new course allocation
- `PUT /api/staff/staff-course-allocations/{id}` - Update existing allocation
- `DELETE /api/staff/staff-course-allocations/{id}` - Delete allocation

### Staff and Course Data
- `GET /api/staff/all-staff` - Get all staff members with filtering
- `GET /api/staff/all-courses` - Get all courses with filtering
- `GET /api/staff/staff/{id}/allocated-courses` - Get courses allocated to specific staff
- `GET /api/staff/course/{id}/allocated-staff` - Get staff allocated to specific course

### Bulk Operations
- `POST /api/staff/bulk-allocations` - Create multiple allocations
- `POST /api/staff/copy-allocations` - Copy allocations from previous session/semester
- `GET /api/staff/allocation-statistics` - Get allocation statistics and analytics

## Frontend Components

### StaffCourseAllocation.vue
**Main Features:**
- **Tabbed Interface**: Switch between viewing allocations and creating new ones
- **Advanced Filtering**: Multi-criteria filtering with real-time updates
- **Statistics Dashboard**: Visual overview of allocation metrics
- **Comprehensive Table**: Detailed allocation listing with actions
- **Modal Details**: In-depth allocation information display

**Key Sections:**
1. **Create Allocation Form**
   - Session, Semester, Level, Programme selection
   - Course and Staff selection with filtering
   - Allocation type and remarks
   - Form validation and error handling

2. **Filter Panel**
   - Session, Semester, Staff, Allocation Type filters
   - Real-time filtering with immediate results
   - Clear and reset functionality

3. **Statistics Cards**
   - Total Allocations count
   - Lecturers, Coordinators, Examiners breakdown
   - Visual representation with color coding

4. **Allocations Table**
   - Staff information with avatar
   - Course details with code and title
   - Programme and level context
   - Session/semester information
   - Allocation type badges
   - Status indicators
   - Action buttons (View, Edit, Toggle Status)

5. **Allocation Details Modal**
   - Complete allocation information
   - Staff and course details
   - Allocation context and metadata
   - Remarks and additional notes

## Usage Examples

### Creating a Course Allocation
```javascript
const allocationData = {
    session_id: 1,
    semester: 1,
    level_id: 2,
    programme_id: 5,
    course_id: 25,
    staff_id: 10,
    allocation_type: 'lecturer',
    remarks: 'Primary lecturer for Computer Science 200 Level'
};

await post('/api/staff/staff-course-allocations', allocationData);
```

### Filtering Allocations
```javascript
const filters = {
    session_id: 1,
    semester: 1,
    staff_id: 10,
    allocation_type: 'lecturer'
};

const allocations = await get('/api/staff/staff-course-allocations', { params: filters });
```

### Bulk Allocation Creation
```javascript
const bulkAllocations = {
    allocations: [
        {
            staff_id: 10,
            course_id: 25,
            session_id: 1,
            semester: 1,
            programme_id: 5,
            level_id: 2,
            allocation_type: 'lecturer'
        },
        {
            staff_id: 11,
            course_id: 26,
            session_id: 1,
            semester: 1,
            programme_id: 5,
            level_id: 2,
            allocation_type: 'coordinator'
        }
    ]
};

await post('/api/staff/bulk-allocations', bulkAllocations);
```

### Copying Allocations from Previous Session
```javascript
const copyData = {
    from_session_id: 1,
    from_semester: 1,
    to_session_id: 2,
    to_semester: 1,
    allocation_types: ['lecturer', 'coordinator'],
    staff_ids: [10, 11, 12]
};

await post('/api/staff/copy-allocations', copyData);
```

## Business Logic

### Allocation Rules
1. **Uniqueness**: One staff member cannot have multiple allocations of the same type for the same course in the same session/semester
2. **Role Hierarchy**: Course Coordinator can also submit results, External Examiner can approve results
3. **Status Control**: Only active allocations are considered for result submission/approval
4. **Audit Trail**: All allocations track who created them and when

### Permission System
- **View Allocations**: `can_view_staff_allocations`
- **Manage Allocations**: `can_manage_staff_allocations`
- **View Staff**: `can_view_staff`
- **View Courses**: `can_view_courses`

### Validation Rules
- **Required Fields**: staff_id, course_id, session_id, semester, programme_id, level_id, allocation_type
- **Allocation Types**: Must be one of 'lecturer', 'coordinator', 'examiner'
- **Semester Range**: Must be between 1 and 3
- **Foreign Key Validation**: All referenced entities must exist
- **Remarks Length**: Maximum 500 characters

## Integration with Result Management

### Result Submission Permissions
- **Lecturers**: Can submit course results
- **Coordinators**: Can submit and approve course results
- **Examiners**: Can approve course results

### Workflow Integration
1. **Course Allocation** → Staff assigned to courses
2. **Result Submission** → Allocated staff submit results
3. **Result Approval** → Coordinators/Examiners approve results
4. **Result Compilation** → System compiles approved results

## Statistics and Analytics

### Available Metrics
- **Total Allocations**: Overall count of course allocations
- **Active vs Inactive**: Status distribution
- **Role Distribution**: Breakdown by allocation type
- **Top Allocated Staff**: Staff with most course assignments
- **Top Allocated Courses**: Courses with most staff assignments

### Reporting Features
- **Export Functionality**: Export allocation data for reporting
- **Filter-based Analytics**: Statistics based on current filters
- **Historical Comparison**: Compare allocations across sessions

## Benefits

### 1. Streamlined Course Management
- **Centralized Assignment**: Single interface for all course allocations
- **Role Clarity**: Clear definition of staff responsibilities
- **Efficient Workflow**: Streamlined process from allocation to result management

### 2. Enhanced Accountability
- **Audit Trail**: Complete history of allocation changes
- **Permission Control**: Role-based access to allocation functions
- **Status Tracking**: Clear visibility of active/inactive allocations

### 3. Improved Efficiency
- **Bulk Operations**: Handle multiple allocations simultaneously
- **Copy Functionality**: Replicate successful allocation patterns
- **Advanced Filtering**: Quickly find specific allocations

### 4. Better Planning
- **Statistics Dashboard**: Overview of allocation distribution
- **Historical Data**: Track allocation patterns over time
- **Resource Optimization**: Identify over/under-allocated resources

This Staff Course Management System provides a comprehensive solution for managing the complex relationships between staff, courses, and academic responsibilities in higher education institutions.
