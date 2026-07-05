package zehntausend.app.data.repository

import zehntausend.app.data.model.ApiResponse
import zehntausend.app.data.model.GameState
import zehntausend.app.data.network.RetrofitClient

class GameRepository {

    private val api = RetrofitClient.api

    suspend fun createGame(playerName: String, aiCount: Int = 0): Result<ApiResponse> = runCatching {
        api.createGame(playerName, aiCount).body() ?: throw Exception("Leere Antwort")
    }

    suspend fun joinGame(gameCode: String, playerName: String): Result<ApiResponse> = runCatching {
        api.joinGame(gameCode, playerName).body() ?: throw Exception("Leere Antwort")
    }

    suspend fun startGame(gameId: Int, playerId: Int, token: String): Result<ApiResponse> = runCatching {
        api.startGame(gameId, playerId, token).body() ?: throw Exception("Leere Antwort")
    }

    suspend fun rollDice(gameId: Int, playerId: Int, token: String): Result<GameState> = runCatching {
        api.rollDice(gameId, playerId, token).body() ?: throw Exception("Leere Antwort")
    }

    suspend fun keepDice(gameId: Int, playerId: Int, indices: List<Int>, token: String): Result<GameState> = runCatching {
        val indicesStr = indices.joinToString(",")
        api.keepDice(gameId, playerId, indicesStr, token).body() ?: throw Exception("Leere Antwort")
    }

    suspend fun bank(gameId: Int, playerId: Int, token: String): Result<ApiResponse> = runCatching {
        api.bank(gameId, playerId, token).body() ?: throw Exception("Leere Antwort")
    }

    suspend fun getState(gameId: Int, playerId: Int, token: String): Result<GameState> = runCatching {
        api.getState(gameId, playerId, token).body() ?: throw Exception("Leere Antwort")
    }

    suspend fun aiTurn(gameId: Int): Result<GameState> = runCatching {
        api.aiTurn(gameId).body() ?: throw Exception("Leere Antwort")
    }
}
