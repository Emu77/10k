package zehntausend.app.data.repository

import com.google.gson.Gson
import retrofit2.Response
import zehntausend.app.data.model.ApiResponse
import zehntausend.app.data.model.GameState
import zehntausend.app.data.network.RetrofitClient

class GameRepository {
    private val api = RetrofitClient.api
    private val gson = Gson()

    // Liest bei einer Fehlerantwort den echten "error"-Text aus dem JSON-Body des Servers,
    // statt immer nur "Leere Antwort" zu zeigen.
    private fun <T> extractErrorMessage(response: Response<T>): String {
        val rawError = response.errorBody()?.string()
        if (!rawError.isNullOrBlank()) {
            return try {
                val parsed = gson.fromJson(rawError, ApiResponse::class.java)
                parsed.error?.takeIf { it.isNotBlank() } ?: rawError
            } catch (e: Exception) {
                rawError
            }
        }
        return "Serverfehler (HTTP ${response.code()})"
    }

    private fun <T> Response<T>.bodyOrThrow(): T {
        if (isSuccessful) {
            return body() ?: throw Exception("Leere Antwort")
        }
        throw Exception(extractErrorMessage(this))
    }

    suspend fun createGame(playerName: String, aiCount: Int = 0, winScore: Int = 10000): Result<ApiResponse> = runCatching {
        api.createGame(playerName, aiCount, winScore).bodyOrThrow()
    }
    suspend fun joinGame(gameCode: String, playerName: String): Result<ApiResponse> = runCatching {
        api.joinGame(gameCode, playerName).bodyOrThrow()
    }
    suspend fun startGame(gameId: Int, playerId: Int, token: String): Result<ApiResponse> = runCatching {
        api.startGame(gameId, playerId, token).bodyOrThrow()
    }
    suspend fun rollDice(gameId: Int, playerId: Int, token: String): Result<GameState> = runCatching {
        api.rollDice(gameId, playerId, token).bodyOrThrow()
    }
    suspend fun keepDice(gameId: Int, playerId: Int, indices: List<Int>, token: String): Result<GameState> = runCatching {
        val indicesStr = indices.joinToString(",")
        api.keepDice(gameId, playerId, indicesStr, token).bodyOrThrow()
    }
    suspend fun bank(gameId: Int, playerId: Int, token: String): Result<ApiResponse> = runCatching {
        api.bank(gameId, playerId, token).bodyOrThrow()
    }
    suspend fun getState(gameId: Int, playerId: Int, token: String): Result<GameState> = runCatching {
        api.getState(gameId, playerId, token).bodyOrThrow()
    }
    suspend fun aiTurn(gameId: Int): Result<GameState> = runCatching {
        api.aiTurn(gameId).bodyOrThrow()
    }
    suspend fun finishChoice(token: String, choice: String): Result<ApiResponse> = runCatching {
        api.finishChoice(token, choice).bodyOrThrow()
    }
}
