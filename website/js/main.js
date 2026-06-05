const apiUrl = "http://localhost/dev-wsl/BattleOfBrains/api/";

async function apiRequest(endpoint, method = "GET", data = null) {
  const options = {
    method,
    headers: {
      "Content-Type": "application/json",
    },
  };

  options.cache = "no-store";

  try {
    const userId = localStorage.getItem("userId");
    if (userId) {
      options.headers["X-User-Id"] = userId;
    }
  } catch (e) {}
  if (data) {
    options.body = JSON.stringify(data);
    console.log("Request body:", options.body);
  }

  const response = await fetch(`${apiUrl}${endpoint}`, options);
  console.log(
    `API Request: ${method} ${endpoint}`,
    data ? `with data: ${JSON.stringify(data)}` : "without data",
  );
  console.log(
    "API Response status:",
    response.status,
    "Response body:",
    await response.clone().text(),
  );

  if (!response.ok) {
    const errorData = await response.json();
    throw new Error(errorData.message || "API request failed");
  }

  return response.json();
}

document.addEventListener("DOMContentLoaded", () => {
  function safeNavigate(url) {
    try {
      let input = url;
      if (/^\/?pages\//.test(input)) {
        input = '/' + input.replace(/^\/+/, '');
      }
      let target = new URL(input, window.location.href).href;

      while (target.indexOf('/pages/pages/') !== -1) {
        target = target.replace('/pages/pages/', '/pages/');
      }

        console.log('[NavigationTrace] safeNavigate called. target:', target, 'input:', url, 'current:', window.location.href);
        console.trace();

      if (window.location.href === target) return;
      const now = Date.now();
      if (
        window._lastNavigateTarget === target &&
        now - (window._lastNavigateTime || 0) < 3000
      )
        return;
      if (window._navigating) return;
      window._navigating = true;
      window._lastNavigateTarget = target;
      window._lastNavigateTime = now;
      console.log('safeNavigate ->', target);
      try {
        sessionStorage.setItem('_lastNavigateTarget', target);
        sessionStorage.setItem('_lastNavigateTime', String(now));
        sessionStorage.setItem('_suppressRedirectUntil', String(now + 5000));
      } catch (e) {}
      setTimeout(() => {
        try {
          window._navigating = false;
        } catch (e) {}
      }, 5000);
      window.location.href = target;
    } catch (e) {
      try {
        let fallback = url;
        if (/^\/?pages\//.test(fallback)) {
          fallback = '/' + fallback.replace(/^\/+/, '');
        }
        while (fallback.indexOf('pages/pages/') !== -1) {
          fallback = fallback.replace('pages/pages/', 'pages/');
        }
        if (window.location.href === fallback) return;
        const now = Date.now();
        if (
          window._lastNavigateTarget === fallback &&
          now - (window._lastNavigateTime || 0) < 3000
        )
          return;
        if (window._navigating) return;
        window._navigating = true;
        window._lastNavigateTarget = fallback;
        window._lastNavigateTime = now;
        console.log('safeNavigate (fallback) ->', fallback);
        try {
          sessionStorage.setItem('_lastNavigateTarget', fallback);
          sessionStorage.setItem('_lastNavigateTime', String(now));
          sessionStorage.setItem('_suppressRedirectUntil', String(now + 5000));
        } catch (e) {}
        setTimeout(() => {
          try {
            window._navigating = false;
          } catch (e) {}
        }, 5000);
        window.location.href = fallback;
      } catch (e2) {
        console.error('safeNavigate failed', e2);
      }
    }
  }
  if (
    !localStorage.getItem("userId") &&
    !window.location.href.endsWith("login.html") &&
    !window.location.href.endsWith("register.html")
  ) {
    safeNavigate("pages/login.html");
  } else if (
    !localStorage.getItem("userId") &&
    window.location.href.endsWith("login.html")
  ) {
    const loginForm = document.getElementById("loginForm");
    const canselButton = document.getElementById("cancel");
    canselButton.addEventListener("click", () => {
      safeNavigate("../index.html");
    });
    loginForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const username = document.getElementById("username").value;
      const password = document.getElementById("password").value;

      console.log("Login attempt with username:", username);
      console.log("Login attempt with password:", password);

      try {
        const response = await apiRequest("users/", "POST", {
          username,
          password,
        });
        localStorage.setItem("userId", response.user.id);
        safeNavigate("../index.html");
      } catch (error) {
        alert(error.message);
      }
    });
  } else if (
    localStorage.getItem("userId") &&
    window.location.href.endsWith("login.html")
  ) {
    safeNavigate("../index.html");
  }

  if (window.location.href.endsWith("register.html")) {
    const registerForm = document.getElementById("registerForm");
    const canselButton = document.getElementById("cancel");
    canselButton.addEventListener("click", () => {
      safeNavigate("../index.html");
    });
    registerForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const username = document.getElementById("username").value;
      const password = document.getElementById("password").value;

      try {
        const response = await apiRequest("users/", "PUT", {
          username,
          password,
        });
        safeNavigate("../index.html");
      } catch (error) {
        alert(error.message);
      }
    });
  }

  if (window.location.href.endsWith("index.html")) {
    const logoutButton = document.getElementById("logout");
    const playButton = document.getElementById("play");

    logoutButton.addEventListener("click", () => {
      localStorage.removeItem("userId");
      safeNavigate("pages/login.html");
    });

    playButton.addEventListener("click", () => {
      safeNavigate("pages/rooms.html");
    });
  }

  if (window.location.href.endsWith("rooms.html")) {
    const userId = localStorage.getItem("userId");
    if (!userId) {
      safeNavigate("../pages/login.html");
      return;
    }

    apiRequest("game/")
      .then((rooms) => {
        const roomsContainer = document.getElementById("roomsContainer");
        rooms.forEach((room) => {
          const roomCard = document.createElement("div");
          roomCard.className = "card mb-3";
          const isAdminRoom = String(room.admin) === String(userId);
          const buttonClass = isAdminRoom
            ? "btn btn-outline-primary enter-btn"
            : "btn btn-primary join-btn";
          const buttonText = isAdminRoom ? "Enter Room" : "Join Room";
          const playersCount = Array.isArray(room.players)
            ? room.players.length
            : 0;
          roomCard.innerHTML = `
                            <div class="card-body">
                                <h5 class="card-title">Room ${room.id}</h5>
                                <p class="card-text">Players: ${playersCount}/${room.capacity}</p>
                                <button class="${buttonClass}" data-room-id="${room.id}" data-admin-id="${room.admin}">${buttonText}</button>
                            </div>
                        `;
          roomsContainer.appendChild(roomCard);
        });
      })
      .catch((error) => {
        alert("Failed to load rooms: " + error.message);
      });

    const backButton = document.getElementById("back");
    backButton.addEventListener("click", () => {
      safeNavigate("../index.html");
    });

    document.getElementById("roomsContainer").addEventListener("click", (e) => {
      if (e.target.classList.contains("join-btn")) {
        const roomId = e.target.getAttribute("data-room-id");
        apiRequest("game/", "PUT", {
          id: userId,
          action: "join",
          roomID: roomId,
        })
          .then(() => {
            safeNavigate(`game.html?roomID=${roomId}`);
          })
          .catch((error) => {
            alert("Failed to join room: " + error.message);
          });
      } else if (e.target.classList.contains("enter-btn")) {
          const roomId = e.target.getAttribute("data-room-id");
          safeNavigate(`game.html?roomID=${roomId}`);
        }
    });
  }

  if (window.location.href.includes("game.html?roomID=")) {
    const urlParams = new URLSearchParams(window.location.search);
    const roomId = urlParams.get("roomID");
    const userId = localStorage.getItem("userId");

    if (!userId) {
      safeNavigate("../pages/login.html");
      return;
    }
    let pollTimer = null;
    let hasNavigatedToQuestion = false;
    let consecutiveStartedCount = 0;
    // cache last seen room snapshot to avoid unnecessary DOM updates
    let lastRoomKey = null;

    const startButton = document.getElementById("startGame");
    const stopButton = document.getElementById("stopGame");
    const questionListEl = document.getElementById("questionList");
    let roomQuestions = [];

    function renderPlayers(room) {
      const playerList = document.getElementById("playerList");
      const adminDisplay = document.getElementById("adminDisplay");
      if (adminDisplay) adminDisplay.innerHTML = "";
      playerList.innerHTML = "";
      const players = Array.isArray(room.players) ? room.players.map(String) : [];
      const isAdminLocal = String(room.admin) === String(userId);
      players.forEach((playerId) => {
        const li = document.createElement("li");
        li.className = "d-flex justify-content-between align-items-center mb-2";
        const nameSpan = document.createElement("span");
        nameSpan.textContent = `Player ${playerId}`;
        li.appendChild(nameSpan);
        if (isAdminLocal && String(playerId) !== String(userId)) {
          const btn = document.createElement("button");
          btn.className = "btn btn-sm btn-outline-danger kick-btn";
          btn.setAttribute("data-player-id", playerId);
          btn.textContent = "Kick";
          li.appendChild(btn);
        }
        playerList.appendChild(li);
      });


    
      if (room.admin && adminDisplay) {
        const isPlaying =
          Array.isArray(room.players) &&
          room.players.map(String).includes(String(room.admin));
        apiRequest(`users/?id=${encodeURIComponent(room.admin)}`)
          .then((user) => {
            adminDisplay.textContent =
              `Admin: ${user.username}` +
              (isPlaying ? " (playing)" : " (spectating)");
          })
          .catch(() => {
            adminDisplay.textContent =
              `Admin: ${room.admin}` +
              (isPlaying ? " (playing)" : " (spectating)");
          });
      }
    }

    function fetchRoomAndUpdate() {
      apiRequest(`game/?roomID=${roomId}`)
        .then((room) => {
          // 1) Check membership first (always): redirect if removed
          try {
            const playersNow = Array.isArray(room.players) ? room.players.map(String) : [];
            const amAdminNow = String(room.admin) === String(userId);
            if (!amAdminNow && !playersNow.includes(String(userId))) {
              console.log('[GameRoom] current user not in players list; redirecting to rooms');
              try {
                const kickUntil = Date.now() + 5000;
                localStorage.setItem('kickedRoom', JSON.stringify({ room: roomId, until: kickUntil }));
              } catch (e) {}
              if (pollTimer) {
                clearInterval(pollTimer);
                pollTimer = null;
              }
              safeNavigate('../pages/rooms.html');
              return;
            }
          } catch (e) {}

          // 2) navigation decisions (started state)
          let skipJoinBecauseKicked = false;
          try {
            const kicked = JSON.parse(localStorage.getItem("kickedRoom") || "null");
            if (kicked && kicked.room === roomId) {
              if (kicked.until > Date.now()) {
                skipJoinBecauseKicked = true;
              } else {
                localStorage.removeItem("kickedRoom");
              }
            }
          } catch (e) {}

          if (room.started) {
            if (skipJoinBecauseKicked) {
              console.log("[RoomWatcher] skipping auto-join due to recent kick for room", roomId);
              consecutiveStartedCount = 0;
            } else {
              consecutiveStartedCount += 1;
            }
          } else {
            consecutiveStartedCount = 0;
          }

          if (consecutiveStartedCount >= 2) {
            console.log('[GamePoll] consecutiveStartedCount=', consecutiveStartedCount, 'hasNavigatedToQuestion=', hasNavigatedToQuestion);
            const isAdmin = String(room.admin) === String(userId);
            let suppressUntil = 0;
            try {
              suppressUntil = Number(sessionStorage.getItem('_suppressRedirectUntil') || '0');
            } catch (e) {
              suppressUntil = 0;
            }
            const now = Date.now();
            if (suppressUntil && now < suppressUntil) {
              console.log('[RoomWatcher] suppressed redirect to question due to recent navigation marker', new Date(suppressUntil).toISOString());
            } else if (!isAdmin) {
              if (hasNavigatedToQuestion) {
                console.log('[GamePoll] navigation to question already performed; skipping');
              } else {
                hasNavigatedToQuestion = true;
                if (pollTimer) clearInterval(pollTimer);
                console.log('[GamePoll] navigating to question for room', roomId);
                safeNavigate(`question.html?roomID=${roomId}`);
                return;
              }
            } else {
              console.log('[RoomWatcher] admin detected, skipping auto-redirect to question page');
            }
          }

          // 3) change detection: skip DOM updates if nothing meaningful changed
          const playersArr = Array.isArray(room.players) ? room.players.map(String).sort() : [];
          const snapshot = JSON.stringify({ started: !!room.started, admin: String(room.admin || ''), players: playersArr });
          if (lastRoomKey !== null && lastRoomKey === snapshot) {
            // no meaningful change, skip heavy UI updates
            console.log('[GamePoll] no room changes detected; skipping UI update');
            return;
          }
          lastRoomKey = snapshot;

          // 4) perform UI updates (questions, players, buttons) when change detected
          try {
            const isAdmin = String(room.admin) === String(userId);
            if (questionListEl) questionListEl.style.display = isAdmin ? 'block' : 'none';
          } catch (e) {}

          if (questionListEl && (!Array.isArray(roomQuestions) || roomQuestions.length === 0)) {
            apiRequest(`game/questions.php?roomID=${roomId}`)
              .then((qs) => {
                roomQuestions = qs || [];
                if (!Array.isArray(roomQuestions) || roomQuestions.length === 0) {
                  questionListEl.innerHTML = '<small class="text-muted">No questions configured.</small>';
                } else {
                  const html = roomQuestions
                    .map((q, idx) => `<div class="mb-2"><strong>${idx + 1}.</strong> ${q.question}</div>`)
                    .join('');
                  questionListEl.innerHTML = `<h6>Questions</h6>${html}`;
                }
              })
              .catch(() => {});
          }
          renderPlayers(room);
          try {
            if (startButton && stopButton) {
              if (String(room.admin) === String(userId)) {
                if (room.started) {
                  startButton.style.display = "none";
                  stopButton.style.display = "inline-block";
                } else {
                  startButton.style.display = "inline-block";
                  stopButton.style.display = "none";
                }
              } else {
                startButton.style.display = "none";
                stopButton.style.display = "none";
              }
            }
          } catch (e) {}
        })
        .catch((error) => {
          console.error("Failed to load room details:", error);
        });
    }

    fetchRoomAndUpdate();
    pollTimer = setInterval(fetchRoomAndUpdate, 1500);

    const _playerListContainer = document.getElementById("playerList");
    if (_playerListContainer) {
      _playerListContainer.addEventListener("click", (e) => {
        if (e.target.classList.contains("kick-btn")) {
          const targetId = e.target.getAttribute("data-player-id");
          if (!confirm(`Kick player ${targetId} from this room?`)) return;
          console.log('[Admin] kicking player', targetId, 'from room', roomId);
          apiRequest("game/", "PUT", { id: targetId, action: "leave", roomID: roomId })
            .then((updatedRoom) => {
              alert(`Player ${targetId} kicked.`);
              try { fetchRoomAndUpdate(); } catch (e) {}
            })
            .catch((err) => {
              console.error('Failed to kick player', err);
              alert('Failed to kick player: ' + (err && err.message ? err.message : err));
            });
        }
      });
    }

    const leaveButton = document.getElementById("leaveRoom");
    leaveButton.addEventListener("click", () => {
      apiRequest("game/", "PUT", {
        id: userId,
        action: "leave",
        roomID: roomId,
      })
        .then(() => {
          if (pollTimer) clearInterval(pollTimer);
          safeNavigate("../pages/rooms.html");
        })
        .catch((error) => {
          alert("Failed to leave room: " + error.message);
        });
    });

    if (startButton) {
      startButton.addEventListener("click", () => {
        if (!confirm("Are you sure you want to start the game?")) return;
        apiRequest("game/", "PUT", {
          id: userId,
          action: "start",
          roomID: roomId,
        })
          .then(() => {
            if (pollTimer) clearInterval(pollTimer);
            try {
              fetchRoomAndUpdate();
            } catch (e) {}
          })
          .catch((error) => {
            alert("Failed to start game: " + error.message);
          });
      });
    }

    if (stopButton) {
      stopButton.addEventListener("click", () => {
        if (!confirm("Stop the game for all players?")) return;
        apiRequest("game/", "PUT", {
          id: userId,
          action: "stop",
          roomID: roomId,
        })
          .then(() => {
            if (pollTimer) clearInterval(pollTimer);
            fetchRoomAndUpdate();
          })
          .catch((error) => {
            alert("Failed to stop game: " + error.message);
          });
      });
    }
  }

  if (window.location.href.includes("question.html")) {
    const urlParams = new URLSearchParams(window.location.search);
    const roomId = urlParams.get("roomID");
    const userId = localStorage.getItem("userId");

    if (!userId) {
      safeNavigate("../pages/login.html");
      return;
    }

    let questions = [];
    let currentIndex = 0;
    let score = 0;
    let roomWatcher = null;
    let adminWatcher = null;
    let isAdmin = false;

    const roomTitle = document.getElementById("roomTitle");
    const questionNumberEl = document.getElementById("questionNumber");
    const totalQuestionsEl = document.getElementById("totalQuestions");
    const questionText = document.getElementById("questionText");
    const optionsList = document.getElementById("optionsList");
    const submitBtn = document.getElementById("submitAnswer");
    const leaveBtn = document.getElementById("leaveGame");

    apiRequest(`game/questions.php?roomID=${roomId}`)
      .then((qs) => {
        questions = qs;
        if (!Array.isArray(questions) || questions.length === 0) {
          alert("No questions available in this room.");
          safeNavigate("../pages/rooms.html");
          return;
        }
        totalQuestionsEl.textContent = questions.length;
        roomTitle.textContent = roomId;

        renderQuestion();
        apiRequest("leaderboard.php", "POST", {
          id: userId,
          score: score,
          roomID: roomId,
          progress: { currentIndex: currentIndex, total: questions.length },
          finished: false,
        }).catch(() => {});
        initRoomWatcher();
      })
      .catch((err) => {
        alert("Failed to load questions: " + err.message);
      });

    function initRoomWatcher() {
      const stopBtn = document.getElementById("stopGame");
      let navigatedBack = false;
      let isAdminLocal = false;

      apiRequest(`game/?roomID=${roomId}`)
        .then((room) => {
          isAdmin = String(room.admin) === String(userId);
          isAdminLocal = isAdmin;
          if (stopBtn) stopBtn.style.display = isAdmin ? "inline-block" : "none";
        })
        .catch((err) => console.error("Failed to fetch room for watcher:", err));

      roomWatcher = setInterval(async () => {
        try {
          const room = await apiRequest(`game/?roomID=${roomId}`);
          console.log(`[RoomWatcher] room.started=${room.started}`);
          // If admin stopped the quiz while players are on question page, return to rooms immediately for non-admins
          if (!room.started && !isAdminLocal && !navigatedBack) {
            let suppressUntil = 0;
            try {
              suppressUntil = Number(sessionStorage.getItem('_suppressRedirectUntil') || '0');
            } catch (e) {
              suppressUntil = 0;
            }
            const now = Date.now();
            if (suppressUntil && now < suppressUntil) {
              console.log('[RoomWatcher] suppressed return-to-rooms due to recent navigation marker', new Date(suppressUntil).toISOString());
            } else {
              navigatedBack = true;
              if (roomWatcher) {
                clearInterval(roomWatcher);
                roomWatcher = null;
              }
              // redirect players back to the room waiting view (not rooms list)
              console.log("[RoomWatcher] detected stopped state; navigating back to room waiting view");
              safeNavigate(`game.html?roomID=${roomId}`);
            }
          }
        } catch (err) {
          console.error("Room watcher error:", err);
        }
      }, 1500);
    }

    function renderQuestion() {
      const q = questions[currentIndex];
      questionNumberEl.textContent = currentIndex + 1;
      questionText.textContent = q.question || "";
      optionsList.innerHTML = "";
      (q.options || []).forEach((opt, i) => {
        const div = document.createElement("div");
        div.className = "form-check text-start mb-2";
        const inputId = `opt_${i}`;
        div.innerHTML = `
                    <input class="form-check-input" type="radio" name="options" id="${inputId}" value="${opt}">
                    <label class="form-check-label" for="${inputId}">${opt}</label>
                `;
        optionsList.appendChild(div);
      });
    }

    submitBtn.addEventListener("click", () => {
      const selected = document.querySelector('input[name="options"]:checked');
      if (!selected) {
        alert("Please select an answer.");
        return;
      }
      const answer = selected.value;
      const correct = questions[currentIndex].answer;
      if (answer === correct) score += 1;
      currentIndex += 1;

      const progressPayload = {
        currentIndex: currentIndex,
        total: questions.length,
      };
      apiRequest("leaderboard.php", "POST", {
        id: userId,
        score: score,
        roomID: roomId,
        progress: progressPayload,
        finished: currentIndex >= questions.length,
      }).catch(() => {});

      if (currentIndex < questions.length) {
        renderQuestion();
      } else {
        submitBtn.disabled = true;
        optionsList.innerHTML = "";
        questionText.innerHTML = `<h3>Your score: ${score}/${questions.length}</h3>`;
        if (roomWatcher) {
          clearInterval(roomWatcher);
          roomWatcher = null;
        }
        apiRequest("leaderboard.php", "POST", {
          id: userId,
          score: score,
          roomID: roomId,
          finished: true,
        })
          .then(() => {
            safeNavigate(
              `leaderboard.html?roomID=${encodeURIComponent(roomId)}`,
            );
          })
          .catch((err) => {
            console.error("Failed to submit score:", err);
            alert("Failed to submit score: " + err.message);
          });
      }
    });

    leaveBtn.addEventListener("click", () => {
      if (roomWatcher) {
        clearInterval(roomWatcher);
        roomWatcher = null;
      }
      if (adminWatcher) {
        clearInterval(adminWatcher);
        adminWatcher = null;
      }
      apiRequest("game/", "PUT", {
        id: userId,
        action: "leave",
        roomID: roomId,
      })
        .then(() => {
          safeNavigate("../pages/rooms.html");
        })
        .catch((error) => {
          alert("Failed to leave room: " + error.message);
        });
    });

    const stopBtn = document.getElementById("stopGame");
    if (stopBtn) {
      stopBtn.addEventListener("click", () => {
        if (!confirm("Stop the quiz for all players?")) return;
        apiRequest("game/", "PUT", {
          id: userId,
          action: "stop",
          roomID: roomId,
        })
          .then(() => {
            if (roomWatcher) {
              clearInterval(roomWatcher);
              roomWatcher = null;
            }
            if (adminWatcher) {
              clearInterval(adminWatcher);
              adminWatcher = null;
            }
            safeNavigate("../pages/rooms.html");
          })
          .catch((err) => {
            alert("Failed to stop quiz: " + err.message);
          });
      });
    }
  }

  if (window.location.href.includes("admin_monitor.html")) {
    const urlParams = new URLSearchParams(window.location.search);
    const roomId = urlParams.get("roomID");
    if (roomId) safeNavigate(`game.html?roomID=${roomId}`);
  }
  if (window.location.href.endsWith("leaderboard.html")) {
    const userId = localStorage.getItem("userId");
    if (!userId) {
      safeNavigate("../pages/login.html");
      return;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const roomIdParam = urlParams.get("roomID");
    let pollTimer = null;

    const backBtn = document.getElementById("backToRooms");
    backBtn.addEventListener("click", () => {
      if (pollTimer) clearInterval(pollTimer);
      safeNavigate("rooms.html");
    });

    function renderLeaderboard(list) {
      const tbody = document.getElementById("leaderboardBody");
      tbody.innerHTML = "";
      list.forEach((u, idx) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
                    <th scope="row">${idx + 1}</th>
                    <td>${u.username}</td>
                    <td>${u.score ? u.score : 0}</td>
                `;
        tbody.appendChild(tr);
      });
    }

    function fetchLeaderboard() {
      const endpoint = roomIdParam
        ? `leaderboard.php?roomID=${encodeURIComponent(roomIdParam)}`
        : "leaderboard.php?top=50";
      apiRequest(endpoint)
        .then((list) => renderLeaderboard(list))
        .catch((err) => console.error("Failed to load leaderboard:", err));
    }

    fetchLeaderboard();
    pollTimer = setInterval(fetchLeaderboard, 1500);
  }
});
