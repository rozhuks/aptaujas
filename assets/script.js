// SASKARNE: viss JavaScript vienuviet

// ---------- 1. Poga "Kopet saiti" ----------

document.querySelectorAll('.copy-link').forEach(function (button) {
    button.addEventListener('click', function () {
        const link = button.dataset.link;
        navigator.clipboard.writeText(link).then(function () {
            button.textContent = 'Nokopēts!';
            setTimeout(function () { button.textContent = 'Kopēt saiti'; }, 1500);
        }).catch(function () {
            prompt('Nokopē saiti:', link);
        });
    });
});

// ---------- 2. Diagrammas (tikai rezultatu lapa, izmanto Chart.js) ----------

document.querySelectorAll('canvas.chart').forEach(function (canvas) {
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: JSON.parse(canvas.dataset.labels),
            datasets: [{
                data: JSON.parse(canvas.dataset.counts),
                backgroundColor: '#0866ff',
                borderRadius: 4,
                maxBarThickness: 60
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
});

// ---------- 3. Jautajumu veidosana (tikai edit.php lapa) ----------

if (document.getElementById('survey-form')) {
    const form = document.getElementById('survey-form');
    const hiddenInput = document.getElementById('questions-input');
    const list = document.getElementById('questions');

    const modal = document.getElementById('modal');
    const mText = document.getElementById('m-text');
    const mType = document.getElementById('m-type');
    const mBox = document.getElementById('m-choices-box');
    const mChoices = document.getElementById('m-choices');
    const mError = document.getElementById('m-error');

    // Jautajumi glabajas saja masiva lidz "Saglabat"
    let questions = JSON.parse(hiddenInput.value || '[]');

    const TRASH_ICON = '<svg viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/></svg>';

    // Pasarga no HTML koda ievietosanas tekstaa
    function esc(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function hasChoices(type) {
        return type === 'single' || type === 'multiple';
    }

    // Ka jautajums izskatisies (priekskatijums)
    function preview(q) {
        if (q.type === 'text') {
            return '<input class="input input-half" placeholder="Ievadiet savu atbildi" disabled>';
        }
        if (q.type === 'scale') {
            return '<div class="scale"><div class="scale-labels"><span>1</span><span>2</span><span>3</span><span>4</span><span>5</span></div>'
                + '<input type="range" min="1" max="5" value="3" disabled></div>';
        }
        const inputType = q.type === 'single' ? 'radio' : 'checkbox';
        return '<div class="choices">' + q.choices.map(function (c) {
            return '<label class="choice"><input type="' + inputType + '" disabled> ' + esc(c) + '</label>';
        }).join('') + '</div>';
    }

    // Uzzime visus jautajumus no jauna
    function render() {
        if (questions.length === 0) {
            list.innerHTML = '<div class="card"><p class="muted">Vēl nav neviena jautājuma. Spied "+ Pievienot jautājumu".</p></div>';
            return;
        }
        list.innerHTML = questions.map(function (q, i) {
            return '<div class="card">'
                + '<button type="button" class="del-btn" data-index="' + i + '" title="Dzēst">' + TRASH_ICON + '</button>'
                + '<h2 class="q-title">' + (i + 1) + '. ' + esc(q.text) + '</h2>'
                + preview(q)
                + '</div>';
        }).join('');
    }

    // Dzesanas poga
    list.addEventListener('click', function (event) {
        const button = event.target.closest('.del-btn');
        if (button) {
            questions.splice(Number(button.dataset.index), 1);
            render();
        }
    });

    // Viena atbilzu varianta rinda logaa
    function addChoiceRow(value) {
        const row = document.createElement('div');
        row.className = 'choice-row';
        const inputType = mType.value === 'single' ? 'radio' : 'checkbox';
        row.innerHTML = '<input type="' + inputType + '" disabled>'
            + '<input class="input" maxlength="100" placeholder="Atbilde">'
            + '<button type="button" class="x-btn" title="Noņemt">×</button>';
        row.querySelector('.input').value = value || '';
        row.querySelector('.x-btn').addEventListener('click', function () { row.remove(); });
        mChoices.appendChild(row);
    }

    // Mainot veidu, paradam vai paslepjam atbilzu variantus
    mType.addEventListener('change', function () {
        const show = hasChoices(mType.value);
        mBox.hidden = !show;
        mChoices.querySelectorAll('input[disabled]').forEach(function (input) {
            input.type = mType.value === 'single' ? 'radio' : 'checkbox';
        });
        if (show && mChoices.children.length === 0) {
            addChoiceRow();
            addChoiceRow();
            addChoiceRow();
        }
    });

    function openModal() {
        mText.value = '';
        mType.value = '';
        mChoices.innerHTML = '';
        mBox.hidden = true;
        mError.textContent = '';
        modal.hidden = false;
        mText.focus();
    }

    function closeModal() {
        modal.hidden = true;
    }

    // Parbauda un pievieno jautajumu
    function saveQuestion() {
        const text = mText.value.trim();
        const type = mType.value;
        const choices = Array.from(mChoices.querySelectorAll('.input'))
            .map(function (input) { return input.value.trim(); })
            .filter(function (value) { return value !== ''; });

        if (text.length < 3) {
            mError.textContent = 'Jautājumam jābūt vismaz 3 simboliem.';
            return;
        }
        if (!type) {
            mError.textContent = 'Izvēlies atbilžu veidu.';
            return;
        }
        if (hasChoices(type) && (choices.length < 2 || choices.length > 10)) {
            mError.textContent = 'Vajag no 2 līdz 10 atbilžu variantiem.';
            return;
        }

        questions.push({ text: text, type: type, choices: hasChoices(type) ? choices : [] });
        render();
        closeModal();
    }

    document.getElementById('open-modal').addEventListener('click', openModal);
    document.getElementById('m-cancel').addEventListener('click', closeModal);
    document.getElementById('m-save').addEventListener('click', saveQuestion);
    document.getElementById('m-add-choice').addEventListener('click', function () { addChoiceRow(); });

    // Aizver logu ar Escape vai klikski uz fona
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeModal();
    });
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });

    // Pirms suta formu, ieliekam jautajumus slepta lauka
    form.addEventListener('submit', function () {
        hiddenInput.value = JSON.stringify(questions);
    });

    render();
}
