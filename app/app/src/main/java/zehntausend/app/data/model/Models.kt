package zehntausend.app.data.model

import com.google.gson.annotations.SerializedName

data class ApiResponse(
    val ok: Boolean = false,
    val error: String? = null,
    val code: String? = null,
    val token: String? = null,
    val game_id: Int? = null,
    val player_id: Int? = null,
    val slot: Int? = null
)

data class GameState(
    val ok: Boolean = false,
    val status: String = "",
    @SerializedName("current_slot") val current_player_id: Int = 0,
    val dice: List<Int> = emptyList(),
    val kept: List<Int> = emptyList(),
    @SerializedName("turn_score") val turn_score: Int = 0,
    val players: List<Player> = emptyList(),
    val message: String = "",
    val winner: String? = null
)

data class Player(
    @SerializedName("slot") val player_id: Int = 0,
    val name: String = "",
    @SerializedName("score") val total_score: Int = 0,
    val is_ai: Boolean = false
)