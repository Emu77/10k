package zehntausend.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.runtime.*
import androidx.lifecycle.viewmodel.compose.viewModel
import zehntausend.app.ui.screens.*
import zehntausend.app.ui.theme.ZehntausendTheme
import zehntausend.app.viewmodel.GameViewModel

enum class Screen { LOGIN, LOBBY, GAME, RESULT }

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContent {
            ZehntausendTheme {
                val viewModel: GameViewModel = viewModel()
                var screen by remember { mutableStateOf(Screen.LOGIN) }

                when (screen) {
                    Screen.LOGIN -> LoginScreen(
                        viewModel = viewModel,
                        onGameCreated = { screen = Screen.LOBBY },
                        onGameJoined = { screen = Screen.LOBBY }
                    )
                    Screen.LOBBY -> LobbyScreen(
                        viewModel = viewModel,
                        onGameStarted = { screen = Screen.GAME }
                    )
                    Screen.GAME -> GameScreen(
                        viewModel = viewModel,
                        onGameOver = { screen = Screen.RESULT }
                    )
                    Screen.RESULT -> ResultScreen(
                        viewModel = viewModel,
                        onPlayAgain = { screen = Screen.LOGIN }
                    )
                }
            }
        }
    }
}
