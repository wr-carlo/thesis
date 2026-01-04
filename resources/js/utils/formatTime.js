/**
 * Format a date to human-readable relative time
 * @param {string|Date} date - The date to format
 * @returns {string} - Human-readable time string (e.g., "2 minutes ago", "3 hours ago")
 */
export function formatTimeAgo(date) {
    if (!date) return "N/A";

    const now = new Date();
    const past = new Date(date);
    const diffInSeconds = Math.floor((now - past) / 1000);

    // Less than a minute
    if (diffInSeconds < 60) {
        return diffInSeconds <= 1 ? "just now" : `${diffInSeconds} seconds ago`;
    }

    // Less than an hour
    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) {
        return diffInMinutes === 1
            ? "1 minute ago"
            : `${diffInMinutes} minutes ago`;
    }

    // Less than a day
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) {
        return diffInHours === 1 ? "1 hour ago" : `${diffInHours} hours ago`;
    }

    // Less than a month (30 days)
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 30) {
        return diffInDays === 1 ? "1 day ago" : `${diffInDays} days ago`;
    }

    // Less than a year (365 days)
    const diffInMonths = Math.floor(diffInDays / 30);
    if (diffInMonths < 12) {
        return diffInMonths === 1
            ? "1 month ago"
            : `${diffInMonths} months ago`;
    }

    // Years
    const diffInYears = Math.floor(diffInDays / 365);
    return diffInYears === 1 ? "1 year ago" : `${diffInYears} years ago`;
}
