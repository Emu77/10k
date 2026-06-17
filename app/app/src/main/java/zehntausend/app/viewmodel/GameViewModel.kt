package zehntausend.app.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import zehntausend.app.data.model.GameState
import zehntausend.app.data.repository.GameRepository

data class UiState(
    val isLoading: Boolean = false,
    val error: String? = null,
    val gameId: Int = 0,
    val playerId: Int = 0,
    val playerName: String = "",
    val gameCode: String = "",
    val token: String = "",
    val gameState: GameState? = null,
    val selectedDice: Set<Int> = emptySet()
)

class GameViewModel : ViewModel() {

    private val repository = GameRepository()
    private val _uiState = MutableStateFlow(UiState())
    val uiState: StateFlow<UiState> = _uiState

    fun createGame(playerName: String, onSuccess: () -> Unit) {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, error = null)
            repository.createGame(playerName)
                .onSuccess { response ->
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        gameId = response.game_id ?: 0,
                        playerId = response.player_id ?: 0,
                        playerName = playerName,
                        gameCode = response.code ?: "",
                        token = response.token ?: ""
                    )
                    onSuccess()
                }
                .onFailure { _uiState.value = _uiState.value.copy(isLoading = false, error = it.message) }
        }
    }

    fun joinGame(gameCode: String, playerName: String, onSuccess: () -> Unit) {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, error = null)
            repository.joinGame(gameCode, playerName)
                .onSuccess { response ->
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        gameId = response.game_id ?: 0,
                        playerId = response.player_id ?: 0,
                        playerName = playerName,
                        gameCode = gameCode
                    )
                    onSuccess()
                }
                .onFailure { _uiState.value = _uiState.value.copy(isLoading = false, error = it.message) }
        }
    }

    fun startGame(onSuccess: () -> Unit) {
        viewModelScope.launch {
            val state = _uiState.value
            _uiState.value = state.copy(isLoading = true, error = null)
            repository.startGame(state.gameId, state.playerId, state.token)
                .onSuccess { _uiState.value = _uiState.value.copy(isLoading = false); onSuccess() }
                .onFailure { _uiState.value = _uiState.value.copy(isLoading = false, error = it.message) }
        }
    }

    fun rollDice() {
        viewModelScope.launch {
            val state = _uiState.value
            _uiState.value = state.copy(isLoading = true, error = null)
            repository.rollDice(state.gameId, state.playerId)
                .onSuccess { _uiState.value = _uiState.value.copy(isLoading = false, gameState = it, selectedDice = emptySet()) }
                .onFailure { _uiState.value = _uiState.value.copy(isLoading = false, error = it.message) }
        }
    }

    fun toggleDie(index: Int) {
        val current = _uiState.value.selectedDice.toMutableSet()
        if (current.contains(index)) current.remove(index) else current.add(index)
        _uiState.value = _uiState.value.copy(selectedDice = current)
    }

    fun keepDice() {
        viewModelScope.launch {
            val state = _uiState.value
            if (state.selectedDice.isEmpty()) return@launch
            _uiState.value = state.copy(isLoading = true, error = null)
            repository.keepDice(state.gameId, state.playerId, state.selectedDice.toList())
                .onSuccess { _uiState.value = _uiState.value.copy(isLoading = false, gameState = it, selectedDice = emptySet()) }
                .onFailure { _uiState.value = _uiState.value.copy(isLoading = false, error = it.message) }
        }
    }

    fun bank(onSuccess: () -> Unit) {
        viewModelScope.launch {
            val state = _uiState.value
            _uiState.value = state.copy(isLoading = true, error = null)
            repository.bank(state.gameId, state.playerId)
                .onSuccess { _uiState.value = _uiState.value.copy(isLoading = false, gameState = it); onSuccess() }
                .onFailure { _uiState.value = _uiState.value.copy(isLoading = false, error = it.message) }
        }
    }

    fun refreshState() {
        viewModelScope.launch {
            val state = _uiState.value
            repository.getState(state.gameId, state.playerId)
                .onSuccess { _uiState.value = _uiState.value.copy(gameState = it) }
                .onFailure { _uiState.value = _uiState.value.copy(error = it.message) }
        }
    }
}
