<?php
$pageTitle = "Subnet Quiz";
$pageDesc = "Test your networking knowledge with our interactive subnetting quiz.";

ob_start();
?>

<main id="main">
    <section class="blog" style="padding-bottom:0;margin-bottom:0;">
        <div class="container" data-aos="fade-up">
            <div class="section-header">
                <h2>Subnet Quiz</h2>
                <h3 style="color:#FFFFFF;">What are the network address, first host address, last host address,
                    broadcast address, and the subnet mask for a host with the IP Address below?</h3>
            </div>
        </div>
    </section>
    <section id="blog" class="blog" style="background-color:#000000;">
        <div class="container" data-aos="fade-up">
            <div class="row" style="width:100%;">
                <input type="hidden" name="countdown" id="txtcountdown">
                <div class="section-content">
                    <h3 style="color:#FFFFFF;"><i class="fa fa-check-circle" style="color:#5cb85c;"></i> Correct Ans
                        : <span id="correctCount" style="color:#5cb85c;">0</span> <i class="fa fa-times-circle"
                            style="color:#fcc500;padding-left:10px;"></i> Give Up : <span id="giveUpCount"
                            style="color:#fcc500;">0</span> <span style="padding-left:10px;"><a href="#"
                                id="clearScoreBtn" class="btn btn-primary btn-sm"
                                style="padding:4px 4px 2px 4px;line-height:20px;">Clear</a></span></h3>
                </div>
            </div>
            <form name="calculator" id="calculator" method="post" action="">
                <div class="row" class="convertion-section" style="width:100%;">
                    <div class="col-md-12"></div>
                    <div class="col-md-6 form-line">
                        <div class="form-group">
                            <label>
                                <h3>IP Address</h3>
                            </label>
                            <div style="width:100%;color:#FFFFFF;">
                                <div id="ip-octet-1" class="task taskIP">253</div>
                                <div id="ip-octet-2" class="task taskIP">118</div>
                                <div id="ip-octet-3" class="task taskIP">117</div>
                                <div id="ip-octet-4" class="task taskIP">112</div>
                                <div class="task" id="taskBitmask"><span id="ip-mask-value">/23</span><input type="hidden" name="mask_bits"
                                        id="mask_bits" value="23" size="10" readonly></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>
                                <h3>Network Address <span id="status-network" class="answer-status"></span></h3>
                            </label>
                            <div style="width:100%;">
                                <div class="qtext1"><input type="text" class="form-control textform" name="NetAddO1"
                                        value="" id="NetAddO1"></div>
                                <div class="qtext2"><input type="text" class="form-control textform" name="NetAddO2"
                                        value="" id="NetAddO2"></div>
                                <div class="qtext2"><input type="text" class="form-control textform" name="NetAddO3"
                                        value="" id="NetAddO3"></div>
                                <div class="qtext2"><input type="text" class="form-control textform" name="NetAddO4"
                                        value="" id="NetAddO4"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label style="padding-top:10px;">
                                <h3>First Host Address <span id="status-firstHost" class="answer-status"></span></h3>
                            </label>
                            <div style="width:100%;">
                                <div class="qtext1"><input type="text" class="form-control textform" name="fhost1"
                                        value="" id="fhost1"></div>
                                <div class="qtext2"><input type="text" class="form-control textform" name="fhost2"
                                        value="" id="fhost2"></div>
                                <div class="qtext2"><input type="text" class="form-control textform" name="fhost3"
                                        value="" id="fhost3"></div>
                                <div class="qtext2"><input type="text" class="form-control textform" name="fhost4"
                                        value="" id="fhost4"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label style="padding-top:10px;">
                                <h3>Last Host Address <span id="status-lastHost" class="answer-status"></span></h3>
                            </label>
                            <div style="width:100%;">
                                <div class="qtext1"><input type="text" class="form-control textform" name="lhost1"
                                        value="" id="lhost1"></div>
                                <div class="qtext2"><input type="text" class="form-control textform" name="lhost2"
                                        value="" id="lhost2"></div>
                                <div class="qtext2"><input type="text" class="form-control textform" name="lhost3"
                                        value="" id="lhost3"></div>
                                <div class="qtext2"><input type="text" class="form-control textform" name="lhost4"
                                        value="" id="lhost4"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label style="padding-top:10px;">
                                <h3>Broadcast Address <span id="status-broadcast" class="answer-status"></span></h3>
                            </label>
                            <div style="width:100%;">
                                <div class="qtext1"><input type="text"
                                        class="form-control textform user-answer broadcast" name="BroadAddO1"
                                        value="" id="BroadAddO1"></div>
                                <div class="qtext2"><input type="text"
                                        class="form-control textform user-answer broadcast" name="BroadAddO2"
                                        value="" id="BroadAddO2"></div>
                                <div class="qtext2"><input type="text"
                                        class="form-control textform user-answer broadcast" name="BroadAddO3"
                                        value="" id="BroadAddO3"></div>
                                <div class="qtext2"><input type="text"
                                        class="form-control textform user-answer broadcast" name="BroadAddO4"
                                        value="" id="BroadAddO4"></div>
                            </div>
                        </div>
                        <div class="form-group" style="padding-top:10px;">
                            <label style="padding-top:10px;">
                                <h3>Subnet Mask <span id="status-subnetMask" class="answer-status"></span></h3>
                            </label>
                            <div style="width:100%;">
                                <div class="qtext1"><input type="text"
                                        class="form-control textform user-answer subnet" name="SubnetMaskO1"
                                        value="" id="SubnetMaskO1"></div>
                                <div class="qtext2"><input type="text"
                                        class="form-control textform user-answer subnet" name="SubnetMaskO2"
                                        value="" id="SubnetMaskO2"></div>
                                <div class="qtext2"><input type="text"
                                        class="form-control textform user-answer subnet" name="SubnetMaskO3"
                                        value="" id="SubnetMaskO3"></div>
                                <div class="qtext2"><input type="text"
                                        class="form-control textform user-answer subnet" name="SubnetMaskO4"
                                        value="" id="SubnetMaskO4"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <h3>Type your answer in the text box and click "Check Answers" to see your result.</h3>
                            <h3>Stumped? Click "Give Up" to see the answer.</h3>
                        </div>
                        <div>
                            <input type="button" class="btn btn-success btn-lg" value="Check Answer"
                                id="btnCheckAnswer">&nbsp;&nbsp;
                            <input type="button" class="btn btn-info btn-lg" value="Next"
                                id="btnNext">&nbsp;&nbsp;
                            <input type="button" class="btn btn-primary btn-lg" value="Give Up?"
                                id="cmdgiveup">
                        </div>
                        <div class="history-panel">
                            <h4>History (Last <span id="historyCount">0</span>/50)</h4>
                            <div class="history-table-wrap">
                                <table class="history-table">
                                    <thead>
                                        <tr>
                                            <th>Question</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historyList"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
</main>

<script>
    (function () {
        const STORAGE_KEY = "subnetQuizProgressV1";
        const HISTORY_LIMIT = 50;
        let correctCount = 0;
        let giveUpCount = 0;
        let currentQuestionAnswered = false;
        let currentAnswers = null;
        let questionHistory = [];
        let currentHistoryIndex = -1;

        const fieldMap = {
            network: ["NetAddO1", "NetAddO2", "NetAddO3", "NetAddO4"],
            firstHost: ["fhost1", "fhost2", "fhost3", "fhost4"],
            lastHost: ["lhost1", "lhost2", "lhost3", "lhost4"],
            broadcast: ["BroadAddO1", "BroadAddO2", "BroadAddO3", "BroadAddO4"],
            subnetMask: ["SubnetMaskO1", "SubnetMaskO2", "SubnetMaskO3", "SubnetMaskO4"]
        };
        const inputIdToFieldKey = {};
        Object.keys(fieldMap).forEach((fieldKey) => {
            fieldMap[fieldKey].forEach((id) => {
                inputIdToFieldKey[id] = fieldKey;
            });
        });

        const statusMap = {
            network: "status-network",
            firstHost: "status-firstHost",
            lastHost: "status-lastHost",
            broadcast: "status-broadcast",
            subnetMask: "status-subnetMask"
        };

        function readStorage() {
            try {
                const raw = localStorage.getItem(STORAGE_KEY);
                if (!raw) return null;
                return JSON.parse(raw);
            } catch (_error) {
                return null;
            }
        }

        function writeStorage() {
            updateActiveHistorySnapshot();
            const inputValues = collectInputValues();
            const statusValues = collectStatusValues();
            const data = {
                correctCount,
                giveUpCount,
                currentQuestionAnswered,
                currentAnswers,
                questionHistory,
                currentHistoryIndex,
                inputValues,
                statusValues
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        }

        function toInt(octets) {
            return (((octets[0] << 24) >>> 0) | (octets[1] << 16) | (octets[2] << 8) | octets[3]) >>> 0;
        }

        function toOctets(value) {
            return [
                (value >>> 24) & 255,
                (value >>> 16) & 255,
                (value >>> 8) & 255,
                value & 255
            ];
        }

        function randomInt(min, max) {
            return Math.floor(Math.random() * (max - min + 1)) + min;
        }

        function randomValidFirstOctet() {
            const value = randomInt(1, 223);
            if (value === 127) {
                return randomValidFirstOctet();
            }
            return value;
        }

        function questionKey(question) {
            return question.ip.join(".") + "/" + question.prefix;
        }

        function parseQuestionKey(key) {
            if (typeof key !== "string" || key.indexOf("/") === -1) return null;
            const parts = key.split("/");
            const ipPart = parts[0].split(".").map((v) => Number(v));
            const prefix = Number(parts[1]);
            if (ipPart.length !== 4 || ipPart.some((n) => Number.isNaN(n) || n < 0 || n > 255)) return null;
            if (ipPart[0] === 127 || ipPart[0] === 0 || ipPart[0] > 223) return null;
            if (Number.isNaN(prefix) || prefix < 8 || prefix > 30) return null;
            return { ip: ipPart, prefix };
        }

        function buildQuestionFromIpPrefix(ip, prefix) {
            const ipInt = toInt(ip);
            const maskInt = (0xFFFFFFFF << (32 - prefix)) >>> 0;
            const networkInt = (ipInt & maskInt) >>> 0;
            const broadcastInt = (networkInt | (~maskInt >>> 0)) >>> 0;
            const firstHostInt = networkInt + 1;
            const lastHostInt = broadcastInt - 1;
            return {
                ip: ip.slice(),
                prefix,
                network: toOctets(networkInt),
                firstHost: toOctets(firstHostInt),
                lastHost: toOctets(lastHostInt),
                broadcast: toOctets(broadcastInt),
                subnetMask: toOctets(maskInt)
            };
        }

        function cloneQuestion(question) {
            return JSON.parse(JSON.stringify(question));
        }

        function collectStatusValues() {
            const statusValues = {};
            Object.keys(statusMap).forEach((fieldKey) => {
                const statusEl = document.getElementById(statusMap[fieldKey]);
                statusValues[fieldKey] = statusEl.className.includes("status-correct")
                    ? "correct"
                    : statusEl.className.includes("status-wrong")
                        ? "wrong"
                        : statusEl.className.includes("status-pending")
                            ? "pending"
                            : "";
            });
            return statusValues;
        }

        function collectInputValues() {
            const inputValues = {};
            Object.keys(fieldMap).forEach((fieldKey) => {
                inputValues[fieldKey] = getFieldValues(fieldKey);
            });
            return inputValues;
        }

        function updateActiveHistorySnapshot() {
            if (currentHistoryIndex < 0 || !questionHistory[currentHistoryIndex]) return;
            questionHistory[currentHistoryIndex].question = cloneQuestion(currentAnswers);
            questionHistory[currentHistoryIndex].inputValues = collectInputValues();
            questionHistory[currentHistoryIndex].statusValues = collectStatusValues();
            questionHistory[currentHistoryIndex].isAnswered = currentQuestionAnswered;
        }

        function loadHistoryAttempt(index) {
            const item = questionHistory[index];
            if (!item || (item.result !== "pending" && item.result !== "giveup")) return;
            if (!item.question && item.key) {
                const parsed = parseQuestionKey(item.key);
                if (parsed) {
                    item.question = buildQuestionFromIpPrefix(parsed.ip, parsed.prefix);
                }
            }
            if (!item.question) return;
            if (item.result === "giveup") {
                item.result = "pending";
                item.inputValues = {};
                item.statusValues = {};
                item.isAnswered = false;
            }
            currentHistoryIndex = index;
            currentQuestionAnswered = false;
            currentAnswers = cloneQuestion(item.question);
            updateDisplayedQuestion(currentAnswers);
            clearInputsAndStatus();
            restoreInputValues(item.inputValues || {});
            restoreStatusValues(item.statusValues || {});
            document.getElementById("btnCheckAnswer").disabled = false;
            writeStorage();
            renderHistory();
        }

        function deleteHistoryItem(index) {
            if (index < 0 || index >= questionHistory.length) return;
            questionHistory.splice(index, 1);
            if (currentHistoryIndex === index) {
                currentHistoryIndex = -1;
            } else if (currentHistoryIndex > index) {
                currentHistoryIndex -= 1;
            }
            renderHistory();
            writeStorage();
        }

        function renderHistory() {
            const listEl = document.getElementById("historyList");
            const countEl = document.getElementById("historyCount");
            if (!listEl || !countEl) return;
            countEl.textContent = String(questionHistory.length);
            listEl.innerHTML = "";
            for (let index = questionHistory.length - 1; index >= 0; index -= 1) {
                const item = questionHistory[index];
                const tr = document.createElement("tr");
                tr.className = "history-item";
                tr.dataset.index = String(index);
                const resultClass = item.result === "correct"
                    ? "history-result-correct"
                    : item.result === "giveup"
                        ? "history-result-giveup"
                        : "history-result-pending";
                if (item.result === "pending" || item.result === "giveup") {
                    tr.classList.add("history-item-resumable");
                }
                if (index === currentHistoryIndex) {
                    tr.classList.add("history-item-active");
                }
                tr.innerHTML = '<td class="history-question">' + item.key + '</td><td><span class="history-result ' + resultClass + '">' + item.result + '</span></td><td><button type="button" class="history-delete-btn" data-delete-index="' + index + '" aria-label="Delete history item"><i class="fa fa-trash"></i></button></td>';
                listEl.appendChild(tr);
            }
        }

        function addQuestionToHistory(question) {
            if (!question) return;
            questionHistory.push({
                key: questionKey(question),
                question: cloneQuestion(question),
                timestamp: Date.now(),
                result: "pending",
                inputValues: {},
                statusValues: {},
                isAnswered: false
            });
            if (questionHistory.length > HISTORY_LIMIT) {
                questionHistory = questionHistory.slice(questionHistory.length - HISTORY_LIMIT);
            }
            currentHistoryIndex = questionHistory.length - 1;
            renderHistory();
        }

        function updateQuestionHistoryResult(result) {
            if (currentHistoryIndex >= 0 && questionHistory[currentHistoryIndex] && questionHistory[currentHistoryIndex].result === "pending") {
                questionHistory[currentHistoryIndex].result = result;
                questionHistory[currentHistoryIndex].isAnswered = true;
                renderHistory();
                return;
            }
            const activeKey = currentAnswers ? questionKey(currentAnswers) : "";
            for (let i = questionHistory.length - 1; i >= 0; i -= 1) {
                if (questionHistory[i].result !== "pending") continue;
                if (!activeKey || questionHistory[i].key === activeKey) {
                    questionHistory[i].result = result;
                    questionHistory[i].isAnswered = true;
                    currentHistoryIndex = i;
                    renderHistory();
                    return;
                }
            }
        }

        function buildQuestion() {
            const prefix = randomInt(8, 30);
            const ip = [randomValidFirstOctet(), randomInt(0, 255), randomInt(0, 255), randomInt(1, 254)];
            return buildQuestionFromIpPrefix(ip, prefix);
        }

        function updateDisplayedQuestion(question) {
            document.getElementById("ip-octet-1").firstChild.nodeValue = String(question.ip[0]);
            document.getElementById("ip-octet-2").firstChild.nodeValue = String(question.ip[1]);
            document.getElementById("ip-octet-3").firstChild.nodeValue = String(question.ip[2]);
            document.getElementById("ip-octet-4").firstChild.nodeValue = String(question.ip[3]);
            document.getElementById("ip-mask-value").textContent = "/" + question.prefix;
            document.getElementById("mask_bits").value = String(question.prefix);
        }

        function clearInputsAndStatus() {
            Object.keys(fieldMap).forEach((key) => {
                fieldMap[key].forEach((id) => {
                    const input = document.getElementById(id);
                    input.value = "";
                    input.style.borderColor = "";
                });
                const statusEl = document.getElementById(statusMap[key]);
                statusEl.innerHTML = "";
                statusEl.className = "answer-status";
            });
        }

        function restoreInputValues(savedInputValues) {
            if (!savedInputValues) return;
            Object.keys(fieldMap).forEach((fieldKey) => {
                const values = Array.isArray(savedInputValues[fieldKey]) ? savedInputValues[fieldKey] : [];
                fieldMap[fieldKey].forEach((id, index) => {
                    document.getElementById(id).value = values[index] || "";
                });
            });
        }

        function restoreStatusValues(savedStatusValues) {
            if (!savedStatusValues) return;
            Object.keys(statusMap).forEach((fieldKey) => {
                const value = savedStatusValues[fieldKey];
                if (value === "correct") {
                    setStatus(fieldKey, true);
                } else if (value === "wrong") {
                    setStatus(fieldKey, false);
                } else if (value === "pending") {
                    setPendingStatus(fieldKey);
                }
            });
        }

        function getFieldValues(fieldKey) {
            return fieldMap[fieldKey].map((id) => document.getElementById(id).value.trim());
        }

        function isValidOctets(values) {
            if (values.some((v) => v === "")) return false;
            return values.every((v) => /^\d+$/.test(v) && Number(v) >= 0 && Number(v) <= 255);
        }

        function setStatus(fieldKey, ok) {
            const statusEl = document.getElementById(statusMap[fieldKey]);
            if (ok) {
                statusEl.className = "answer-status status-correct";
                statusEl.innerHTML = '<i class="fa fa-check"></i>Correct';
            } else {
                statusEl.className = "answer-status status-wrong";
                statusEl.innerHTML = '<i class="fa fa-times"></i>Wrong';
            }
            fieldMap[fieldKey].forEach((id) => {
                document.getElementById(id).style.borderColor = ok ? "#5cb85c" : "#ff4a4a";
            });
        }

        function setPendingStatus(fieldKey) {
            const statusEl = document.getElementById(statusMap[fieldKey]);
            statusEl.className = "answer-status status-pending";
            statusEl.innerHTML = 'Incomplete';
            fieldMap[fieldKey].forEach((id) => {
                document.getElementById(id).style.borderColor = "#f4c542";
            });
        }

        function clearFieldStatus(fieldKey) {
            const statusEl = document.getElementById(statusMap[fieldKey]);
            statusEl.className = "answer-status";
            statusEl.innerHTML = "";
            fieldMap[fieldKey].forEach((id) => {
                document.getElementById(id).style.borderColor = "";
            });
        }

        function fieldMatches(fieldKey) {
            const values = getFieldValues(fieldKey);
            if (!isValidOctets(values)) return null;
            return values.every((v, index) => Number(v) === currentAnswers[fieldKey][index]);
        }

        function checkAnswers() {
            if (!currentAnswers || currentQuestionAnswered) return;
            const keys = Object.keys(fieldMap);
            let allCorrect = true;
            keys.forEach((key) => {
                const ok = fieldMatches(key);
                if (ok === null) {
                    setPendingStatus(key);
                    allCorrect = false;
                } else {
                    setStatus(key, ok);
                    if (!ok) allCorrect = false;
                }
            });
            if (allCorrect) {
                currentQuestionAnswered = true;
                correctCount += 1;
                document.getElementById("correctCount").textContent = String(correctCount);
                document.getElementById("btnCheckAnswer").disabled = true;
                updateQuestionHistoryResult("correct");
            }
            writeStorage();
        }

        function showAnswersAsGiveUp() {
            if (!currentAnswers || currentQuestionAnswered) return;
            Object.keys(fieldMap).forEach((fieldKey) => {
                fieldMap[fieldKey].forEach((id, idx) => {
                    document.getElementById(id).value = String(currentAnswers[fieldKey][idx]);
                });
                setStatus(fieldKey, true);
            });
            currentQuestionAnswered = true;
            giveUpCount += 1;
            document.getElementById("giveUpCount").textContent = String(giveUpCount);
            document.getElementById("btnCheckAnswer").disabled = true;
            updateQuestionHistoryResult("giveup");
            writeStorage();
        }

        function nextQuestion() {
            currentQuestionAnswered = false;
            document.getElementById("btnCheckAnswer").disabled = false;
            currentAnswers = buildQuestion();
            addQuestionToHistory(currentAnswers);
            updateDisplayedQuestion(currentAnswers);
            clearInputsAndStatus();
            writeStorage();
        }

        function clearScores(event) {
            event.preventDefault();
            correctCount = 0;
            giveUpCount = 0;
            currentQuestionAnswered = false;
            questionHistory = [];
            currentHistoryIndex = -1;
            renderHistory();
            document.getElementById("correctCount").textContent = "0";
            document.getElementById("giveUpCount").textContent = "0";
            localStorage.removeItem(STORAGE_KEY);
            nextQuestion();
        }

        function restoreState() {
            const saved = readStorage();
            if (!saved || !saved.currentAnswers) return false;
            currentAnswers = saved.currentAnswers;
            correctCount = Number(saved.correctCount) || 0;
            giveUpCount = Number(saved.giveUpCount) || 0;
            currentQuestionAnswered = Boolean(saved.currentQuestionAnswered);
            questionHistory = Array.isArray(saved.questionHistory) ? saved.questionHistory.slice(-HISTORY_LIMIT) : [];
            currentHistoryIndex = typeof saved.currentHistoryIndex === "number" ? saved.currentHistoryIndex : -1;
            if (currentHistoryIndex >= questionHistory.length) {
                currentHistoryIndex = questionHistory.length - 1;
            }
            if (currentHistoryIndex < 0 && currentAnswers) {
                const activeKey = questionKey(currentAnswers);
                for (let i = questionHistory.length - 1; i >= 0; i -= 1) {
                    if (questionHistory[i].key === activeKey && questionHistory[i].result === "pending") {
                        currentHistoryIndex = i;
                        break;
                    }
                }
                if (currentHistoryIndex < 0 && questionHistory.length) {
                    currentHistoryIndex = questionHistory.length - 1;
                }
            }
            document.getElementById("correctCount").textContent = String(correctCount);
            document.getElementById("giveUpCount").textContent = String(giveUpCount);
            updateDisplayedQuestion(currentAnswers);
            clearInputsAndStatus();
            restoreInputValues(saved.inputValues);
            restoreStatusValues(saved.statusValues);
            document.getElementById("btnCheckAnswer").disabled = currentQuestionAnswered;
            if (!questionHistory.length || questionHistory[questionHistory.length - 1].key !== questionKey(currentAnswers)) {
                addQuestionToHistory(currentAnswers);
            }
            updateActiveHistorySnapshot();
            renderHistory();
            return true;
        }

        $(document).ready(function() {
            document.getElementById("btnCheckAnswer").addEventListener("click", function (event) {
                event.preventDefault();
                checkAnswers();
            });
            document.getElementById("btnNext").addEventListener("click", function (event) {
                event.preventDefault();
                nextQuestion();
            });
            document.getElementById("cmdgiveup").addEventListener("click", function (event) {
                event.preventDefault();
                showAnswersAsGiveUp();
            });
            document.getElementById("clearScoreBtn").addEventListener("click", clearScores);
            document.getElementById("historyList").addEventListener("click", function (event) {
                const deleteButton = event.target.closest("button.history-delete-btn");
                if (deleteButton) {
                    event.preventDefault();
                    event.stopPropagation();
                    const deleteIndex = Number(deleteButton.dataset.deleteIndex);
                    if (!Number.isNaN(deleteIndex)) {
                        deleteHistoryItem(deleteIndex);
                    }
                    return;
                }
                const row = event.target.closest("tr.history-item-resumable");
                if (!row) return;
                const index = Number(row.dataset.index);
                if (Number.isNaN(index)) return;
                loadHistoryAttempt(index);
            });

            Object.keys(fieldMap).forEach((fieldKey) => {
                fieldMap[fieldKey].forEach((id) => {
                    const input = document.getElementById(id);
                    if (input) {
                        input.addEventListener("input", function () {
                            if (!currentQuestionAnswered) {
                                clearFieldStatus(inputIdToFieldKey[id]);
                            }
                            writeStorage();
                        });
                    }
                });
            });

            if (!restoreState()) {
                nextQuestion();
            }

            // Auto-tab between octets
            $('.textform').on('input', function() {
                if (this.value.length >= 3) {
                    const inputs = $('.textform');
                    const index = inputs.index(this);
                    if (index < inputs.length - 1) inputs.eq(index + 1).focus();
                }
            });
        });
    })();
</script>

<?php 
$content = ob_get_clean();
include 'includes/layout.php'; 
?>
