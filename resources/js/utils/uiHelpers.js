import { openConfirmDialog } from "./uiConfirm";

export function confirmAction(message, options = {}) {
    return openConfirmDialog({
        message,
        ...options,
    });
}

export function getErrorMessage(error, fallback = "Произошла ошибка") {
    if (error?.message) {
        return error.message;
    }

    if (typeof error === "string" && error.trim() !== "") {
        return error;
    }

    return fallback;
}
