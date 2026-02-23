/**
 * Central breadcrumb configuration for the app.
 * Maps route names to breadcrumb trails. Uses page props for dynamic labels/params.
 *
 * @param {string} routeName - Current route name from route().current()
 * @param {object} pageProps - Page props from usePage().props
 * @returns {Array<{label: string, route?: string, params?: object|number|array, href?: string, current?: boolean}>}
 */
export function getBreadcrumbItems(routeName, pageProps = {}) {
    if (!routeName) return [];

    const p = pageProps;
    const config = breadcrumbConfig[routeName];

    if (typeof config === "function") {
        return config(p);
    }
    if (Array.isArray(config)) {
        return config;
    }

    return [];
}

/**
 * Route name -> breadcrumb items.
 * Function configs receive pageProps for dynamic labels/params.
 */
const breadcrumbConfig = {
    // Admin - no breadcrumb for dashboard
    "admin.dashboard": [],

    // Admin - Students
    "admin.students.index": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Students", current: true },
    ],
    "admin.students.create": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Students", route: "admin.students.index" },
        { label: "Create", current: true },
    ],
    "admin.students.edit": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Students", route: "admin.students.index" },
        { label: "Edit", current: true },
    ],

    // Admin - Instructors
    "admin.instructors.index": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Instructors", current: true },
    ],
    "admin.instructors.create": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Instructors", route: "admin.instructors.index" },
        { label: "Create", current: true },
    ],
    "admin.instructors.edit": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Instructors", route: "admin.instructors.index" },
        { label: "Edit", current: true },
    ],

    // Admin - Departments, Sections, Subjects
    "admin.departments.index": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Departments", current: true },
    ],
    "admin.departments.create": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Departments", route: "admin.departments.index" },
        { label: "Create", current: true },
    ],
    "admin.departments.edit": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Departments", route: "admin.departments.index" },
        { label: "Edit", current: true },
    ],
    "admin.sections.index": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Sections", current: true },
    ],
    "admin.sections.create": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Sections", route: "admin.sections.index" },
        { label: "Create", current: true },
    ],
    "admin.sections.edit": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Sections", route: "admin.sections.index" },
        { label: "Edit", current: true },
    ],
    "admin.subjects.index": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Subjects", current: true },
    ],
    "admin.subjects.create": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Subjects", route: "admin.subjects.index" },
        { label: "Create", current: true },
    ],
    "admin.subjects.edit": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Subjects", route: "admin.subjects.index" },
        { label: "Edit", current: true },
    ],

    // Admin - Assignments, Logs, Settings
    "admin.assignments.index": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Assignments", current: true },
    ],
    "admin.logs.index": [
        { label: "Dashboard", route: "admin.dashboard" },
        { label: "Logs", current: true },
    ],
    "admin.settings": [],

    // Instructor - Dashboard (no breadcrumb)
    "instructor.dashboard": [],

    // Instructor - Subjects
    "instructor.subjects.index": [{ label: "My Subjects", current: true }],
    "instructor.subjects.requests": [
        { label: "My Subjects", route: "instructor.subjects.index" },
        { label: "Join Requests", current: true },
    ],

    // Instructor - Lessons
    "instructor.lessons.index": [{ label: "Lessons", current: true }],
    "instructor.lessons.create": [
        { label: "Lessons", route: "instructor.lessons.index" },
        { label: "Upload Lesson", current: true },
    ],
    "instructor.lessons.createManual": [
        { label: "Lessons", route: "instructor.lessons.index" },
        { label: "Create Manual", current: true },
    ],
    "instructor.lessons.edit": [
        { label: "Lessons", route: "instructor.lessons.index" },
        { label: "Edit Lesson", current: true },
    ],
    "instructor.lessons.review": [
        { label: "Lessons", route: "instructor.lessons.index" },
        { label: "Review", current: true },
    ],

    // Instructor - Assessment History (accessed from Lessons)
    "instructor.assessments.history": [
        { label: "Lessons", route: "instructor.lessons.index" },
        { label: "Assessment History", current: true },
    ],
    "instructor.assessments.history.student": (p) => {
        const assessmentId = p.assessment?.id;
        const studentId = p.student?.id;
        const studentName = p.student?.name ?? "Student";
        return [
            { label: "Lessons", route: "instructor.lessons.index" },
            {
                label: "Assessment History",
                route: "instructor.assessments.history",
                params: assessmentId ? { assessment: assessmentId } : undefined,
            },
            {
                label: studentName,
                current: true,
            },
        ];
    },

    // Instructor - Settings
    "instructor.settings": [],

    // Student - Dashboard (no breadcrumb)
    "student.dashboard": [],

    // Student - Subjects
    "student.subjects.index": [{ label: "Join Subjects", current: true }],

    // Student - Assessments
    "student.assessments.index": [{ label: "Assessments", current: true }],
    "student.assessments.show": [
        { label: "Assessments", route: "student.assessments.index" },
        { label: "Take Assessment", current: true },
    ],
    "student.assessments.history": [
        { label: "Assessments", route: "student.assessments.index" },
        { label: "History", current: true },
    ],
    "student.assessments.results": (p) => {
        const assessmentId = p.assessment?.id;
        return [
            { label: "Assessments", route: "student.assessments.index" },
            {
                label: "History",
                route: assessmentId ? "student.assessments.history" : "student.assessments.index",
                params: assessmentId ? { assessment: assessmentId } : undefined,
            },
            { label: "Results", current: true },
        ];
    },

    // Student - Settings
    "student.settings": [],
};

export function useBreadcrumbs() {
    return { getBreadcrumbItems };
}
