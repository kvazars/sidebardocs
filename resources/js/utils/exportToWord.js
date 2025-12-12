// utils/exportToWord.js
import { saveAs } from "file-saver";
import { Document, Packer } from "docx";

// Вспомогательные функции
function getQuestionTypeLabel(type) {
    const labels = {
        single: "Одиночный выбор",
        multiple: "Множественный выбор",
        truefalse: "Верно/Неверно",
        text: "Свободный ответ",
        matching: "Сопоставление",
        sorting: "Упорядочивание",
    };
    return labels[type] || type;
}

function getPointsEnding(points) {
    if (points % 10 === 1 && points % 100 !== 11) return "";
    if ([2, 3, 4].includes(points % 10) && ![12, 13, 14].includes(points % 100))
        return "а";
    return "ов";
}

function getCorrectAnswerText(question) {
    switch (question.type) {
        case "single":
            const singleCorrect = question.options?.findIndex(
                (opt) => opt.correct
            );
            return singleCorrect !== -1
                ? `${String.fromCharCode(65 + singleCorrect)}) ${
                      question.options[singleCorrect].text
                  }`
                : "Нет правильного ответа";

        case "multiple":
            const multipleCorrect = question.options
                ?.filter((opt) => opt.correct)
                .map(
                    (opt, index) =>
                        `${String.fromCharCode(
                            65 + question.options.indexOf(opt)
                        )}) ${opt.text}`
                )
                .join(", ");
            return multipleCorrect || "Нет правильных ответов";

        case "truefalse":
            return question.options === "true" ? "Верно" : "Неверно";

        case "text":
            return question.options?.join(", ") || "Нет правильного ответа";

        case "matching":
            if (!question.options) return "Нет пар для сопоставления";
            return question.options
                .map(
                    (pair, idx) =>
                        `${idx + 1} → ${String.fromCharCode(65 + idx)}) ${
                            pair.right
                        }`
                )
                .join("; ");

        case "sorting":
            if (!question.options) return "Нет элементов для сортировки";
            // Для сортировки показываем правильный порядок
            return question.options
                .map((item, idx) => `${idx + 1}. ${item.text}`)
                .join("; ");

        default:
            return "Неизвестный тип вопроса";
    }
}

// Функция для конвертации base64 в ArrayBuffer
function base64ToArrayBuffer(base64) {
    try {
        // Если это data URL, извлекаем base64 часть
        if (base64.startsWith("data:")) {
            const matches = base64.match(/^data:[^;]+;base64,(.+)$/);
            if (matches && matches[1]) {
                base64 = matches[1];
            }
        }

        const binaryString = atob(base64);
        const bytes = new Uint8Array(binaryString.length);
        for (let i = 0; i < binaryString.length; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        return bytes.buffer;
    } catch (error) {
        console.error("Ошибка конвертации base64:", error);
        return null;
    }
}

// Функция перемешивания массива
function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
}

// Основная функция экспорта в Word с ответами
export async function exportTestToWord(test) {
    try {
        const doc = await createTestDocument(test, "withAnswers");
        const blob = await Packer.toBlob(doc);
        const fileName = `test-${(test.title || "export").replace(
            /[^\wа-яА-ЯёЁ\s-]/gi,
            "_"
        )}_с_ответами.docx`;
        saveAs(blob, fileName);
        return true;
    } catch (error) {
        console.error("Ошибка экспорта в Word:", error);
        throw error;
    }
}

// Экспорт без ответов
export async function exportTestToWordWithoutAnswers(test) {
    try {
        const doc = await createTestDocument(test, "withoutAnswers");
        const blob = await Packer.toBlob(doc);
        const fileName = `test-${(test.title || "export").replace(
            /[^\wа-яА-ЯёЁ\s-]/gi,
            "_"
        )}_без_ответов.docx`;
        saveAs(blob, fileName);
        return true;
    } catch (error) {
        console.error("Ошибка экспорта в Word без ответов:", error);
        throw error;
    }
}

// Экспорт с ответами на отдельной странице
export async function exportTestToWordWithSeparateAnswers(test) {
    try {
        const doc = await createTestDocument(test, "separateAnswers");
        const blob = await Packer.toBlob(doc);
        const fileName = `test-${(test.title || "export").replace(
            /[^\wа-яА-ЯёЁ\s-]/gi,
            "_"
        )}_с_ответами_отдельно.docx`;
        saveAs(blob, fileName);
        return true;
    } catch (error) {
        console.error("Ошибка экспорта в Word с отдельными ответами:", error);
        throw error;
    }
}

// Функция для создания документа docx
async function createTestDocument(test, exportType = "withAnswers") {
    const {
        Paragraph,
        TextRun,
        HeadingLevel,
        Table,
        TableRow,
        TableCell,
        WidthType,
        AlignmentType,
        BorderStyle,
        PageBreak,
    } = await import("docx");

    const children = [];
    const totalPoints = test.questions.reduce(
        (sum, q) => sum + (parseInt(q.points) || 0),
        0
    );

    // Заголовок теста
    children.push(
        new Paragraph({
            text: test.title || "Тест",
            heading: HeadingLevel.TITLE,
            alignment: AlignmentType.CENTER,
            spacing: { after: 400 },
        })
    );

    if (exportType === "separateAnswers") {
        children.push(
            new Paragraph({
                children: [
                    new TextRun({
                        text: "ВАЖНО: Эта версия содержит правильные ответы. Для студентов используйте версию без ответов.",
                        bold: true,
                        color: "FF0000",
                    }),
                ],
                spacing: { after: 300 },
            })
        );
    }

    if (test.description) {
        children.push(
            new Paragraph({
                text: test.description,
                spacing: { after: 300 },
            })
        );
    }

    // Информация о тесте
    children.push(
        new Paragraph({
            children: [
                new TextRun({
                    text: "Информация о тесте:",
                    bold: true,
                }),
            ],
            spacing: { after: 200 },
        })
    );

    // Таблица с информацией
    const infoTableRows = [
        new TableRow({
            children: [
                new TableCell({
                    children: [new Paragraph("Время на выполнение:")],
                    width: { size: 50, type: WidthType.PERCENTAGE },
                }),
                new TableCell({
                    children: [new Paragraph(`${test.timeLimit || 30} минут`)],
                    width: { size: 50, type: WidthType.PERCENTAGE },
                }),
            ],
        }),
        new TableRow({
            children: [
                new TableCell({
                    children: [new Paragraph("Всего вопросов:")],
                }),
                new TableCell({
                    children: [new Paragraph(`${test.questions.length}`)],
                }),
            ],
        }),
        new TableRow({
            children: [
                new TableCell({
                    children: [new Paragraph("Всего баллов:")],
                }),
                new TableCell({
                    children: [new Paragraph(`${totalPoints}`)],
                }),
            ],
        }),
    ];

    children.push(
        new Table({
            rows: infoTableRows,
            width: { size: 100, type: WidthType.PERCENTAGE },
        })
    );

    // Система оценивания
    if (test.grading && test.grading.length > 0) {
        children.push(
            new Paragraph({
                text: "",
                spacing: { before: 400, after: 200 },
            }),
            new Paragraph({
                children: [
                    new TextRun({
                        text: "Система оценивания:",
                        bold: true,
                    }),
                ],
            })
        );

        const gradingRows = [
            new TableRow({
                children: [
                    new TableCell({
                        children: [
                            new Paragraph({
                                children: [
                                    new TextRun({
                                        text: "Минимальный балл",
                                        bold: true,
                                    }),
                                ],
                            }),
                        ],
                        width: { size: 33, type: WidthType.PERCENTAGE },
                    }),
                    new TableCell({
                        children: [
                            new Paragraph({
                                children: [
                                    new TextRun({
                                        text: "Максимальный балл",
                                        bold: true,
                                    }),
                                ],
                            }),
                        ],
                        width: { size: 33, type: WidthType.PERCENTAGE },
                    }),
                    new TableCell({
                        children: [
                            new Paragraph({
                                children: [
                                    new TextRun({ text: "Оценка", bold: true }),
                                ],
                            }),
                        ],
                        width: { size: 34, type: WidthType.PERCENTAGE },
                    }),
                ],
            }),
        ];

        test.grading.forEach((grade) => {
            gradingRows.push(
                new TableRow({
                    children: [
                        new TableCell({
                            children: [new Paragraph(`${grade.minScore || 0}`)],
                        }),
                        new TableCell({
                            children: [
                                new Paragraph(`${grade.max_score || 0}`),
                            ],
                        }),
                        new TableCell({
                            children: [new Paragraph(grade.grade || "")],
                        }),
                    ],
                })
            );
        });

        children.push(
            new Table({
                rows: gradingRows,
                width: { size: 100, type: WidthType.PERCENTAGE },
            })
        );
    }

    // Вопросы теста
    children.push(
        new Paragraph({
            text: "",
            spacing: { before: 400, after: 200 },
        }),
        new Paragraph({
            children: [
                new TextRun({
                    text: "Вопросы теста:",
                    bold: true,
                    size: 18,
                }),
            ],
            alignment: AlignmentType.CENTER,
            spacing: { after: 400 },
        })
    );

    // Обрабатываем каждый вопрос
    for (let index = 0; index < test.questions.length; index++) {
        const question = test.questions[index];

        // Номер вопроса
        children.push(
            new Paragraph({
                children: [
                    new TextRun({
                        text: `Вопрос ${index + 1}`,
                        bold: true,
                        size: 16,
                    }),
                ],
                spacing: { before: index === 0 ? 0 : 400, after: 200 },
            })
        );

        // Тип вопроса и баллы
        children.push(
            new Paragraph({
                children: [
                    new TextRun({
                        text: `(${getQuestionTypeLabel(question.type)}) - ${
                            question.points || 1
                        } балл${getPointsEnding(question.points || 1)}`,
                        italics: true,
                    }),
                ],
                spacing: { after: 300 },
            })
        );

        // Текст вопроса
        children.push(
            new Paragraph({
                text: question.text || "",
                spacing: { after: 150 },
            })
        );

        // Изображение вопроса
        if (question.image) {
            try {
                const imageBuffer = base64ToArrayBuffer(question.image);
                if (imageBuffer) {
                    children.push(
                        new Paragraph({
                            children: [
                                new (await import("docx")).ImageRun({
                                    data: imageBuffer,
                                    transformation: {
                                        width: 400,
                                        height: 300,
                                    },
                                }),
                            ],
                            alignment: AlignmentType.CENTER,
                            spacing: { before: 100, after: 150 },
                        })
                    );
                }
            } catch (error) {
                console.error("Ошибка обработки изображения вопроса:", error);
            }
        }

        // Содержимое вопроса
        const questionContent = await getQuestionContent(
            question,
            exportType,
            index
        );
        children.push(...questionContent);

        // Разделитель между вопросами
        if (index < test.questions.length - 1) {
            children.push(
                new Paragraph({
                    text: "",
                    spacing: { before: 200, after: 200 },
                    border: {
                        bottom: {
                            color: "CCCCCC",
                            space: 1,
                            style: BorderStyle.SINGLE,
                            size: 1,
                        },
                    },
                })
            );
        }
    }

    // Ответы на отдельной странице
    if (exportType === "separateAnswers") {
        children.push(
            new Paragraph({
                children: [new PageBreak()],
            }),
            new Paragraph({
                children: [
                    new TextRun({
                        text: "Правильные ответы:",
                        bold: true,
                        size: 32,
                    }),
                ],
                alignment: AlignmentType.CENTER,
                spacing: { before: 200, after: 400 },
            })
        );

        test.questions.forEach((question, index) => {
            children.push(
                new Paragraph({
                    children: [
                        new TextRun({
                            text: `Вопрос ${index + 1}: `,
                            bold: true,
                        }),
                        new TextRun({
                            text: getCorrectAnswerText(question),
                        }),
                    ],
                    spacing: { before: index === 0 ? 0 : 150, after: 50 },
                })
            );
        });
    }

    // Создаем документ
    return new Document({
        sections: [
            {
                properties: {
                    page: {
                        margin: {
                            top: 1000,
                            right: 1000,
                            bottom: 1000,
                            left: 1000,
                        },
                    },
                },
                children: children,
            },
        ],
    });
}

// Функция для получения содержимого вопроса
async function getQuestionContent(question, exportType, questionIndex) {
    const {
        Paragraph,
        TextRun,
        Table,
        TableRow,
        TableCell,
        WidthType,
        AlignmentType,
        ImageRun,
    } = await import("docx");

    const children = [];

    switch (question.type) {
        case "single":
        case "multiple":
            if (question.options && question.options.length > 0) {
                // Определяем правильные ответы
                const correctIndices = question.options
                    .map((opt, idx) => (opt.correct ? idx : -1))
                    .filter((idx) => idx !== -1);

                for (
                    let optIndex = 0;
                    optIndex < question.options.length;
                    optIndex++
                ) {
                    const option = question.options[optIndex];
                    const isCorrect = correctIndices.includes(optIndex);
                    const showAsCorrect =
                        exportType === "withAnswers" && isCorrect;

                    const optionChildren = [
                        new TextRun({
                            text: `${String.fromCharCode(65 + optIndex)}) `,
                            bold: showAsCorrect,
                        }),
                        new TextRun({
                            text: option.text || "",
                            bold: showAsCorrect,
                        }),
                    ];

                    // Изображение варианта ответа
                    if (option.image) {
                        try {
                            const imageBuffer = base64ToArrayBuffer(
                                option.image
                            );
                            if (imageBuffer) {
                                children.push(
                                    new Paragraph({
                                        children: optionChildren,
                                        indent: { left: 400 },
                                        spacing: { after: 50 },
                                    })
                                );

                                children.push(
                                    new Paragraph({
                                        children: [
                                            new ImageRun({
                                                data: imageBuffer,
                                                transformation: {
                                                    width: 200,
                                                    height: 150,
                                                },
                                            }),
                                        ],
                                        indent: { left: 450 },
                                        alignment: AlignmentType.LEFT,
                                        spacing: { before: 10, after: 50 },
                                    })
                                );
                                continue;
                            }
                        } catch (error) {
                            console.error(
                                "Ошибка обработки изображения варианта:",
                                error
                            );
                        }
                    }

                    children.push(
                        new Paragraph({
                            children: optionChildren,
                            indent: { left: 400 },
                            spacing: { after: 100 },
                        })
                    );
                }

                // Поле для ответа
                children.push(
                    new Paragraph({
                        text: "Ответ: _________________________________________________________",
                        spacing: { before: 200, after: 100 },
                    })
                );
            }
            break;

        case "truefalse":
            const correctAnswer = question.options == "true";

            if (exportType === "withAnswers") {
                children.push(
                    new Paragraph({
                        children: [
                            new TextRun({ text: "☐ " }),
                            new TextRun({
                                text: "Верно",
                                bold: correctAnswer,
                            }),
                        ],
                        indent: { left: 400 },
                        spacing: { after: 100 },
                    }),
                    new Paragraph({
                        children: [
                            new TextRun({ text: "☐ " }),
                            new TextRun({
                                text: "Неверно",
                                bold: !correctAnswer,
                            }),
                        ],
                        indent: { left: 400 },
                        spacing: { after: 100 },
                    })
                );
            } else {
                children.push(
                    new Paragraph({
                        children: [new TextRun("☐ Верно")],
                        indent: { left: 400 },
                        spacing: { after: 100 },
                    }),
                    new Paragraph({
                        children: [new TextRun("☐ Неверно")],
                        indent: { left: 400 },
                        spacing: { after: 100 },
                    })
                );
            }

            // Поле для ответа
            children.push(
                new Paragraph({
                    text: "Ответ: _________________________________________________________",
                    spacing: { before: 200, after: 100 },
                })
            );
            break;

        case "text":
            if (
                exportType === "withAnswers" &&
                question.options &&
                question.options.length > 0
            ) {
                children.push(
                    new Paragraph({
                        children: [
                            new TextRun({
                                text: "Правильные ответы: ",
                                bold: true,
                            }),
                            new TextRun({
                                text: question.options.join(", "),
                                bold: true,
                            }),
                        ],
                        indent: { left: 400 },
                        spacing: { after: 150 },
                    })
                );
            }

            // Поле для ответа
            children.push(
                new Paragraph({
                    text: "Ответ студента: _________________________________________________________",
                    spacing: { before: 100, after: 100 },
                })
            );
            break;

        case "matching":
            if (question.options && question.options.length > 0) {
                const leftColumn = [];
                const rightColumn = [];

                // Получаем оригинальные правые части
                const originalRightOptions = question.options.map(
                    (pair) => pair.right || ""
                );

                // Генерируем правые части для отображения
                let displayRightOptions;

                if (
                    exportType === "withAnswers" ||
                    exportType === "separateAnswers"
                ) {
                    // Для версий с ответами - оставляем как есть
                    displayRightOptions = [...originalRightOptions];
                } else {
                    // Для студенческой версии без ответов
                    displayRightOptions = [];

                    // Собираем уникальные правые части
                    const uniqueRightOptions = [
                        ...new Set(originalRightOptions),
                    ];

                    // Если уникальных вариантов достаточно
                    if (
                        uniqueRightOptions.length >= originalRightOptions.length
                    ) {
                        // Берем нужное количество уникальных вариантов
                        const shuffledUnique = [...uniqueRightOptions];
                        shuffleArray(shuffledUnique);
                        displayRightOptions = shuffledUnique.slice(
                            0,
                            originalRightOptions.length
                        );
                    } else {
                        // Если уникальных вариантов меньше, чем нужно
                        // Сначала добавляем все уникальные
                        displayRightOptions = [...uniqueRightOptions];

                        // Затем добавляем дубликаты с номерами до нужного количества
                        let counter = 1;
                        while (
                            displayRightOptions.length <
                            originalRightOptions.length
                        ) {
                            // Берем случайный уникальный вариант
                            const randomIndex = Math.floor(
                                Math.random() * uniqueRightOptions.length
                            );
                            const baseOption = uniqueRightOptions[randomIndex];

                            // Добавляем с номером
                            displayRightOptions.push(
                                `${baseOption} (${counter})`
                            );
                            counter++;
                        }

                        // Перемешиваем окончательный список
                        shuffleArray(displayRightOptions);
                    }
                }

                for (
                    let pairIndex = 0;
                    pairIndex < question.options.length;
                    pairIndex++
                ) {
                    const pair = question.options[pairIndex];

                    // Левая часть
                    leftColumn.push(
                        new Paragraph({
                            children: [
                                new TextRun({
                                    text: `${pairIndex + 1}) `,
                                    bold: true,
                                }),
                                new TextRun({
                                    text: pair.left || "",
                                }),
                            ],
                            spacing: { after: 150 },
                        })
                    );

                    // Изображение левой части
                    if (pair.leftImage) {
                        try {
                            const imageBuffer = base64ToArrayBuffer(
                                pair.leftImage
                            );
                            if (imageBuffer) {
                                leftColumn.push(
                                    new Paragraph({
                                        children: [
                                            new ImageRun({
                                                data: imageBuffer,
                                                transformation: {
                                                    width: 150,
                                                    height: 100,
                                                },
                                            }),
                                        ],
                                        alignment: AlignmentType.CENTER,
                                        spacing: { before: 10, after: 10 },
                                    })
                                );
                            }
                        } catch (error) {
                            console.error(
                                "Ошибка обработки левого изображения:",
                                error
                            );
                        }
                    }

                    // Правая часть
                    const displayRight = displayRightOptions[pairIndex] || "";
                    const isCorrect =
                        exportType === "withAnswers" &&
                        displayRight === pair.right;

                    rightColumn.push(
                        new Paragraph({
                            children: [
                                new TextRun({
                                    text: `${String.fromCharCode(
                                        65 + pairIndex
                                    )}) `,
                                    bold: isCorrect,
                                }),
                                new TextRun({
                                    text: displayRight,
                                    bold: isCorrect,
                                }),
                            ],
                            spacing: { after: 150 },
                        })
                    );
                }

                children.push(
                    new Table({
                        rows: [
                            new TableRow({
                                children: [
                                    new TableCell({
                                        children: [
                                            new Paragraph({
                                                children: [
                                                    new TextRun({
                                                        text: "Левая часть",
                                                        bold: true,
                                                    }),
                                                ],
                                                alignment: AlignmentType.CENTER,
                                            }),
                                            ...leftColumn,
                                        ],
                                        width: {
                                            size: 50,
                                            type: WidthType.PERCENTAGE,
                                        },
                                    }),
                                    new TableCell({
                                        children: [
                                            new Paragraph({
                                                children: [
                                                    new TextRun({
                                                        text: "Правая часть",
                                                        bold: true,
                                                    }),
                                                ],
                                                alignment: AlignmentType.CENTER,
                                            }),
                                            ...rightColumn,
                                        ],
                                        width: {
                                            size: 50,
                                            type: WidthType.PERCENTAGE,
                                        },
                                    }),
                                ],
                            }),
                        ],
                        width: { size: 100, type: WidthType.PERCENTAGE },
                    })
                );

                // Инструкция
                if (
                    exportType === "withAnswers" ||
                    exportType === "separateAnswers"
                ) {
                    children.push(
                        new Paragraph({
                            children: [
                                new TextRun({
                                    text: "Инструкция: ",
                                    bold: true,
                                }),
                                new TextRun({
                                    text: "Сопоставьте элементы из левой колонки с элементами из правой колонки. Правильные соответствия выделены жирным шрифтом.",
                                }),
                            ],
                            spacing: { before: 200, after: 100 },
                        })
                    );
                } else {
                    children.push(
                        new Paragraph({
                            text: "Сопоставьте элементы из левой колонки с элементами из правой колонки:",
                            italics: true,
                            spacing: { before: 200, after: 100 },
                        })
                    );
                }

                // Поля для ответов
                children.push(
                    new Paragraph({
                        text: "Сопоставления студента (впишите буквы):",
                        spacing: { before: 100, after: 10 },
                    })
                );

                question.options.forEach((pair, pairIndex) => {
                    children.push(
                        new Paragraph({
                            text: `${pairIndex + 1}) _________`,
                            indent: { left: 400 },
                            spacing: { after: 5 },
                        })
                    );
                });
            }
            break;

        case "sorting":
            if (question.options && question.options.length > 0) {
                // Для варианта с ответами показываем правильный порядок
                if (exportType === "withAnswers") {
                    children.push(
                        new Paragraph({
                            children: [
                                new TextRun({
                                    text: "Правильный порядок:",
                                    bold: true,
                                }),
                            ],
                            spacing: { before: 100, after: 50 },
                        })
                    );

                    question.options.forEach((item, itemIndex) => {
                        const itemChildren = [
                            new TextRun({
                                text: `${itemIndex + 1}. `,
                                bold: true,
                            }),
                            new TextRun({
                                text: item.text || "",
                                bold: true,
                            }),
                        ];

                        // Изображение элемента
                        if (item.image) {
                            try {
                                const imageBuffer = base64ToArrayBuffer(
                                    item.image
                                );
                                if (imageBuffer) {
                                    children.push(
                                        new Paragraph({
                                            children: itemChildren,
                                            indent: { left: 200 },
                                            spacing: { after: 50 },
                                        })
                                    );

                                    children.push(
                                        new Paragraph({
                                            children: [
                                                new ImageRun({
                                                    data: imageBuffer,
                                                    transformation: {
                                                        width: 200,
                                                        height: 150,
                                                    },
                                                }),
                                            ],
                                            indent: { left: 250 },
                                            alignment: AlignmentType.LEFT,
                                            spacing: { before: 10, after: 50 },
                                        })
                                    );
                                    return;
                                }
                            } catch (error) {
                                console.error(
                                    "Ошибка обработки изображения элемента сортировки:",
                                    error
                                );
                            }
                        }

                        children.push(
                            new Paragraph({
                                children: itemChildren,
                                indent: { left: 200 },
                                spacing: { after: 100 },
                            })
                        );
                    });

                    children.push(
                        new Paragraph({
                            text: "",
                            spacing: { before: 100, after: 100 },
                        })
                    );
                }

                // Перемешанные элементы для студента
                children.push(
                    new Paragraph({
                        children: [
                            new TextRun({
                                text: "Упорядочьте следующие элементы в правильной последовательности:",
                                italics: true,
                            }),
                        ],
                        spacing: {
                            before: exportType === "withAnswers" ? 100 : 0,
                            after: 200,
                        },
                    })
                );

                // Создаем перемешанный список для студента
                const itemsToShow = [...question.options];
                if (exportType !== "withAnswers") {
                    // Перемешиваем для студентов (кроме режима с ответами)
                    shuffleArray(itemsToShow);
                }

                itemsToShow.forEach((item, itemIndex) => {
                    const itemChildren = [
                        new TextRun({
                            text: `${String.fromCharCode(65 + itemIndex)}) `,
                            bold: false,
                        }),
                        new TextRun({
                            text: item.text || "",
                            bold: false,
                        }),
                    ];

                    // Изображение элемента
                    if (item.image) {
                        try {
                            const imageBuffer = base64ToArrayBuffer(item.image);
                            if (imageBuffer) {
                                children.push(
                                    new Paragraph({
                                        children: itemChildren,
                                        indent: { left: 200 },
                                        spacing: { after: 50 },
                                    })
                                );

                                children.push(
                                    new Paragraph({
                                        children: [
                                            new ImageRun({
                                                data: imageBuffer,
                                                transformation: {
                                                    width: 200,
                                                    height: 150,
                                                },
                                            }),
                                        ],
                                        indent: { left: 250 },
                                        alignment: AlignmentType.LEFT,
                                        spacing: { before: 10, after: 50 },
                                    })
                                );
                                return;
                            }
                        } catch (error) {
                            console.error(
                                "Ошибка обработки изображения элемента сортировки:",
                                error
                            );
                        }
                    }

                    children.push(
                        new Paragraph({
                            children: itemChildren,
                            indent: { left: 200 },
                            spacing: { after: 100 },
                        })
                    );
                });

                // Поля для ответов (упорядочивания)
                children.push(
                    new Paragraph({
                        text: "Порядок элементов (впишите буквы в правильном порядке):",
                        spacing: { before: 200, after: 10 },
                    })
                );

                children.push(
                    new Paragraph({
                        text: "Правильная последовательность: __________________________________",
                        spacing: { before: 50, after: 100 },
                    })
                );

                // Альтернативный вариант: таблица для нумерации
                children.push(
                    new Paragraph({
                        text: "Или пронумеруйте элементы в правильном порядке:",
                        spacing: { before: 100, after: 10 },
                    })
                );

                itemsToShow.forEach((item, itemIndex) => {
                    children.push(
                        new Paragraph({
                            text: `${String.fromCharCode(
                                65 + itemIndex
                            )}) _________ - ${item.text || ""}`,
                            indent: { left: 200 },
                            spacing: { after: 5 },
                        })
                    );
                });
            }
            break;

        default:
            children.push(
                new Paragraph({
                    text: "Ответ: _________________________________________________________",
                    spacing: { before: 200, after: 100 },
                })
            );
    }

    return children;
}
