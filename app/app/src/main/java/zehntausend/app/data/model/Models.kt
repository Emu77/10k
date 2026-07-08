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
    val players: List<Player> = emptyList(),
    val message: String = "",
    val winner: String? = null,
    @SerializedName("my_turn") val my_turn: Boolean = false,
    @SerializedName( value = "my_slot") val my_slot: Int = 0,
    val rolled: List<Int> = emptyList(),
    val options: List<DiceOption> = emptyList(),
    val bust: Boolean = false,
    @SerializedName("turn_score") val turn_score: Int = 0,
    @SerializedName("must_choose_finish") val must_choose_finish: Boolean = false,
)

data class DiceOption(
    val dice: List<Int> = emptyList(),
    val score: Int = 0,
    val label: String = ""
)

data class Player(
    @SerializedName("slot") val player_id: Int = 0,
    val name: String = "",
    @SerializedName("total_score") val total_score: Int = 0,
    @SerializedName("is_ai") val is_ai: Int = 0,
    @SerializedName("has_entered") val has_entered: Int = 0,
    @SerializedName("bust_streak") val bust_streak: Int = 0,
    @SerializedName("finish_rank") val finish_rank: Int? = null
)
