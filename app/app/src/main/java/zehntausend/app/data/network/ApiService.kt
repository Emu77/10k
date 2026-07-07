package zehntausend.app.data.network

import retrofit2.Response
import retrofit2.http.Field
import retrofit2.http.FormUrlEncoded
import retrofit2.http.POST
import zehntausend.app.data.model.ApiResponse
import zehntausend.app.data.model.GameState

interface ApiService {
    @FormUrlEncoded
    @POST("api/index.php?action=create")
    suspend fun createGame(
        @Field("name") playerName: String,
        @Field("ai_count") aiCount: Int = 0,
        @Field("win_score") winScore: Int = 10000
    ): Response<ApiResponse>

    @FormUrlEncoded
    @POST("api/index.php?action=join")
    suspend fun joinGame(
        @Field("code") gameCode: String,
        @Field("name") playerName: String
    ): Response<ApiResponse>

    @FormUrlEncoded
    @POST("api/index.php?action=start")
    suspend fun startGame(
        @Field("game_id") gameId: Int,
        @Field("player_id") playerId: Int,
        @Field("token") token: String
    ): Response<ApiResponse>

    @FormUrlEncoded
    @POST("api/index.php?action=roll")
    suspend fun rollDice(
        @Field("game_id") gameId: Int,
        @Field("player_id") playerId: Int,
        @Field("token") token: String
    ): Response<GameState>

    @FormUrlEncoded
    @POST("api/index.php?action=keep")
    suspend fun keepDice(
        @Field("game_id") gameId: Int,
        @Field("player_id") playerId: Int,
        @Field("selected") selected: String,
        @Field("token") token: String
    ): Response<GameState>

    @FormUrlEncoded
    @POST("api/index.php?action=bank")
    suspend fun bank(
        @Field("game_id") gameId: Int,
        @Field("player_id") playerId: Int,
        @Field("token") token: String
    ): Response<ApiResponse>

    @FormUrlEncoded
    @POST("api/index.php?action=state")
    suspend fun getState(
        @Field("game_id") gameId: Int,
        @Field("player_id") playerId: Int,
        @Field("token") token: String
    ): Response<GameState>

    @FormUrlEncoded
    @POST("api/index.php?action=ai_turn")
    suspend fun aiTurn(
        @Field("game_id") gameId: Int
    ): Response<GameState>
}