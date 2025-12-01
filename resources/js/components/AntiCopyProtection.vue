<template>
    <div class="anti-copy-protection">
        <!-- Невидимый компонент защиты -->
    </div>
</template>

<script>
export default {
    name: "AntiCopyProtection",
    mounted() {
        this.addWarningStyles();
        this.setupProtection();
    },
    beforeUnmount() {
        this.removeProtection();
    },
    methods: {
        addWarningStyles() {
            if (!document.getElementById("anti-copy-styles")) {
                const style = document.createElement("style");
                style.id = "anti-copy-styles";
                style.textContent = `
            @keyframes fadeInOut {
              0% { opacity: 0; transform: translate(-50%, -60%); }
              20% { opacity: 1; transform: translate(-50%, -50%); }
              80% { opacity: 1; transform: translate(-50%, -50%); }
              100% { opacity: 0; transform: translate(-50%, -40%); }
            }
            
            .test-execution * {
              user-select: none;
              -webkit-user-select: none;
              -moz-user-select: none;
              -ms-user-select: none;
            }
            
            .test-execution input,
            .test-execution textarea,
            .test-execution select {
              user-select: text;
              -webkit-user-select: text;
              -moz-user-select: text;
              -ms-user-select: text;
            }
          `;
                document.head.appendChild(style);
            }
        },

        setupProtection() {
            document.addEventListener("contextmenu", this.preventCopy);
            document.addEventListener("keydown", this.preventKeyActions);
            document.addEventListener("dragstart", this.preventDrag);
            document.addEventListener("selectstart", this.preventSelection);
        },

        removeProtection() {
            document.removeEventListener("contextmenu", this.preventCopy);
            document.removeEventListener("keydown", this.preventKeyActions);
            document.removeEventListener("dragstart", this.preventDrag);
            document.removeEventListener("selectstart", this.preventSelection);
        },

        // Проверяем, является ли target DOM-элементом с методом closest
        isElementInTestArea(element) {
            return (
                element &&
                typeof element.closest === "function" &&
                element.closest(".test-execution")
            );
        },

        preventCopy(event) {
            if (this.isElementInTestArea(event.target)) {
                event.preventDefault();
                this.showWarning();
                return false;
            }
        },

        preventKeyActions(event) {
            if (this.isElementInTestArea(event.target)) {
                const tagName = event.target.tagName;
                const isEditable = ["INPUT", "TEXTAREA", "SELECT"].includes(
                    tagName
                );

                if (!isEditable) {
                    if (event.ctrlKey) {
                        switch (event.key) {
                            case "c":
                            case "C":
                            case "x":
                            case "X":
                            case "a":
                            case "A":
                            case "u":
                            case "U":
                            case "s":
                            case "S":
                                event.preventDefault();
                                this.showWarning();
                                return false;
                        }
                    }

                    if (event.key.startsWith("F") || event.keyCode === 123) {
                        event.preventDefault();
                        this.showWarning();
                        return false;
                    }
                }
            }
        },

        preventDrag(event) {
            if (this.isElementInTestArea(event.target)) {
                event.preventDefault();
            }
        },

        preventSelection(event) {
            if (this.isElementInTestArea(event.target)) {
                const tagName = event.target.tagName;
                const isEditable = ["INPUT", "TEXTAREA", "SELECT"].includes(
                    tagName
                );

                if (!isEditable) {
                    event.preventDefault();
                }
            }
        },

        showWarning() {
            // Удаляем существующие предупреждения
            const existingWarnings = document.querySelectorAll(
                ".copy-protection-warning"
            );
            existingWarnings.forEach((warning) => {
                if (warning.parentNode) {
                    warning.parentNode.removeChild(warning);
                }
            });

            const warning = document.createElement("div");
            warning.className =
                "alert alert-warning alert-dismissible fade show copy-protection-warning";
            warning.style.cssText = `
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 9999;
                min-width: 300px;
                text-align: center;
            `;
            warning.innerHTML = `
          <i class="bi bi-exclamation-triangle"></i> 
          Копирование и другие действия заблокированы в целях безопасности теста
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

            document.body.appendChild(warning);

            setTimeout(() => {
                if (warning.parentNode) {
                    warning.parentNode.removeChild(warning);
                }
            }, 3000);
        },
    },
};
</script>

<style scoped>
.anti-copy-protection {
    display: none;
}
</style>
