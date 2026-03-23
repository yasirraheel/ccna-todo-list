// CCNA Quiz Logic (Moved outside DOMContentLoaded so it runs on quiz.php which might not use the main init flow)
document.addEventListener('DOMContentLoaded', () => {
  const ccnaSection = document.getElementById('ccna-quiz-section');
  const subnetSection = document.getElementById('subnet-quiz-section');
  const btnTypeSubnet = document.getElementById('btn-type-subnet');
  const btnTypeCcna = document.getElementById('btn-type-ccna');
  const quizMainTitle = document.getElementById('quiz-main-title');
  const quizMainDesc = document.getElementById('quiz-main-desc');

  if (btnTypeSubnet && btnTypeCcna) {
    btnTypeSubnet.addEventListener('click', () => {
      btnTypeSubnet.classList.add('active');
      btnTypeCcna.classList.remove('active');
      subnetSection.classList.remove('app-hidden');
      ccnaSection.classList.add('app-hidden');
      quizMainTitle.textContent = 'Subnet Quiz';
      quizMainDesc.textContent = 'What are the network address, first host address, last host address, broadcast address, and the subnet mask for a host with the IP Address below?';
    });

    btnTypeCcna.addEventListener('click', () => {
      btnTypeCcna.classList.add('active');
      btnTypeSubnet.classList.remove('active');
      ccnaSection.classList.remove('app-hidden');
      subnetSection.classList.add('app-hidden');
      quizMainTitle.textContent = 'CCNA Practice Exam';
      quizMainDesc.textContent = 'Test your knowledge with random multiple-choice questions from the CCNA 200-301 curriculum.';
      if (!currentCcnaQuestion) loadCcnaQuestion();
    });
  }

  let currentCcnaQuestion = null;
  let selectedCcnaOptions = new Set();
  let ccnaCorrect = parseInt(localStorage.getItem('ccnaCorrect') || '0', 10);
  let ccnaIncorrect = parseInt(localStorage.getItem('ccnaIncorrect') || '0', 10);

  const elCcnaQText = document.getElementById('ccna-question-text');
  const elCcnaOpts = document.getElementById('ccna-options-container');
  const elBtnCheckCcna = document.getElementById('btnCheckCcnaAnswer');
  const elBtnNextCcna = document.getElementById('btnNextCcna');
  const elCcnaFeedbackAlert = document.getElementById('ccna-feedback-alert');
  const elCcnaFeedbackTitle = document.getElementById('ccna-feedback-title');
  const elCcnaFeedbackDesc = document.getElementById('ccna-feedback-desc');
  const elCcnaCorrectCnt = document.getElementById('ccnaCorrectCount');
  const elCcnaIncorrectCnt = document.getElementById('ccnaIncorrectCount');
  const elCcnaClearBtn = document.getElementById('ccnaClearScoreBtn');

  function updateCcnaScore() {
    if (elCcnaCorrectCnt) elCcnaCorrectCnt.textContent = ccnaCorrect;
    if (elCcnaIncorrectCnt) elCcnaIncorrectCnt.textContent = ccnaIncorrect;
    localStorage.setItem('ccnaCorrect', ccnaCorrect);
    localStorage.setItem('ccnaIncorrect', ccnaIncorrect);
  }
  updateCcnaScore();

  if (elCcnaClearBtn) {
    elCcnaClearBtn.addEventListener('click', () => {
      ccnaCorrect = 0;
      ccnaIncorrect = 0;
      updateCcnaScore();
    });
  }

  async function loadCcnaQuestion() {
    if (elCcnaQText) elCcnaQText.textContent = 'Loading question...';
    if (elCcnaOpts) elCcnaOpts.innerHTML = '';
    if (elCcnaFeedbackAlert) elCcnaFeedbackAlert.classList.add('app-hidden');
    if (elBtnCheckCcna) {
      elBtnCheckCcna.disabled = true;
      elBtnCheckCcna.classList.remove('app-hidden');
    }
    if (elBtnNextCcna) elBtnNextCcna.classList.add('app-hidden');
    selectedCcnaOptions.clear();
    currentCcnaQuestion = null;

    try {
      // API_BASE is defined globally as '/api/tasks' or a full URL. We need to strip '/tasks' to hit the root API for the quiz endpoints.
      const baseUrl = typeof API_BASE !== 'undefined' ? API_BASE.replace('/tasks', '') : '/api';
      const r = await fetch(`${baseUrl}/quiz`);
      if (!r.ok) throw new Error('Failed to load question');
      const q = await r.json();
      currentCcnaQuestion = q;
      
      if (elCcnaQText) elCcnaQText.textContent = q.question_text;
      if (elCcnaOpts) {
        Object.entries(q.options).forEach(([letter, text]) => {
          const optDiv = document.createElement('div');
          optDiv.className = 'ccna-option';
          optDiv.dataset.letter = letter;
          optDiv.innerHTML = `<span class="ccna-option-letter">${letter})</span> <span class="ccna-option-text">${text}</span>`;
          optDiv.addEventListener('click', () => toggleCcnaOption(letter, optDiv));
          elCcnaOpts.appendChild(optDiv);
        });
      }
    } catch (err) {
      if (elCcnaQText) elCcnaQText.textContent = 'Failed to load question. Please try again later.';
    }
  }

  function toggleCcnaOption(letter, el) {
    if (elBtnCheckCcna && elBtnCheckCcna.classList.contains('app-hidden')) return; // Already checked
    
    if (selectedCcnaOptions.has(letter)) {
      selectedCcnaOptions.delete(letter);
      el.classList.remove('selected');
    } else {
      selectedCcnaOptions.add(letter);
      el.classList.add('selected');
    }
    
    if (elBtnCheckCcna) elBtnCheckCcna.disabled = selectedCcnaOptions.size === 0;
  }

  if (elBtnCheckCcna) {
    elBtnCheckCcna.addEventListener('click', async () => {
      if (!currentCcnaQuestion || selectedCcnaOptions.size === 0) return;
      
      elBtnCheckCcna.disabled = true;
      elBtnCheckCcna.textContent = 'Checking...';

      try {
        const baseUrl = typeof API_BASE !== 'undefined' ? API_BASE.replace('/tasks', '') : '/api';
        const r = await fetch(`${baseUrl}/quiz/check`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            id: currentCcnaQuestion.id,
            selected: Array.from(selectedCcnaOptions)
          })
        });
        
        if (!r.ok) throw new Error('Failed to check answer');
        const res = await r.json();
        
        elBtnCheckCcna.classList.add('app-hidden');
        elBtnNextCcna.classList.remove('app-hidden');
        
        const optionsEls = elCcnaOpts.querySelectorAll('.ccna-option');
        optionsEls.forEach(el => {
          const l = el.dataset.letter;
          if (res.correctAnswers.includes(l)) {
            el.classList.add('correct');
          } else if (selectedCcnaOptions.has(l) && !res.correctAnswers.includes(l)) {
            el.classList.add('incorrect');
          }
        });

        elCcnaFeedbackAlert.classList.remove('app-hidden');
        elCcnaFeedbackAlert.className = `alert ${res.isCorrect ? 'alert-success' : 'alert-danger'}`;
        elCcnaFeedbackTitle.textContent = res.isCorrect ? 'Correct!' : 'Incorrect';
        elCcnaFeedbackDesc.textContent = res.explanation || `The correct answer was: ${res.correctAnswers.join(', ')}`;

        if (res.isCorrect) ccnaCorrect++;
        else ccnaIncorrect++;
        updateCcnaScore();
        
      } catch (err) {
        alert('Error checking answer.');
      } finally {
        elBtnCheckCcna.textContent = 'Check Answer';
      }
    });
  }

  if (elBtnNextCcna) {
    elBtnNextCcna.addEventListener('click', loadCcnaQuestion);
  }
});
