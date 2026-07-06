package zehntausend.app.ui.screens
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import zehntausend.app.viewmodel.GameViewModel

@Composable
fun LobbyScreen(
    viewModel: GameViewModel,
    onGameStarted: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()

    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text("🎲 Lobby", fontSize = 28.sp, fontWeight = FontWeight.Bold)
        Spacer(modifier = Modifier.height(16.dp))
        Card(modifier = Modifier.fillMaxWidth()) {
            Column(modifier = Modifier.padding(16.dp)) {
                Text("Spielcode:", style = MaterialTheme.typography.labelMedium)
                Text(
                    uiState.gameCode,
                    fontSize = 36.sp,
                    fontWeight = FontWeight.Bold,
                    color = MaterialTheme.colorScheme.primary
                )
                Text("Teile diesen Code mit anderen Spielern", style = MaterialTheme.typography.bodySmall)
            }
        }
        Spacer(modifier = Modifier.height(24.dp))
        Text("Spieler:", fontWeight = FontWeight.SemiBold)
        Spacer(modifier = Modifier.height(8.dp))
        val players = uiState.gameState?.players ?: emptyList()
        if (players.isEmpty()) {
            Text("Warte auf Spieler...", style = MaterialTheme.typography.bodyMedium)
        } else {
            LazyColumn {
                items(players) { player ->
                    val label = if (player.is_ai == 1) "🤖 ${player.name}" else "👤 ${player.name}"
                    ListItem(headlineContent = { Text(label) })
                    HorizontalDivider()
                }
            }
        }
        Spacer(modifier = Modifier.weight(1f))
        uiState.error?.let {
            Text(it, color = MaterialTheme.colorScheme.error)
            Spacer(modifier = Modifier.height(8.dp))
        }
        if (uiState.isLoading) {
            CircularProgressIndicator()
        } else if (uiState.gameState?.my_slot == 0) {
            Button(
                onClick = {
                    viewModel.startGame {
                        val state = viewModel.uiState.value.gameState
                        val firstIsAi = state?.players?.firstOrNull()?.is_ai == 1
                        if (firstIsAi) viewModel.triggerAiTurn()
                        onGameStarted()
                    }
                },
                modifier = Modifier.fillMaxWidth()
            ) { Text("Spiel starten") }
        } else {
            Text(
                "Warte, bis der Host das Spiel startet...",
                style = MaterialTheme.typography.bodyMedium
            )
        }
    }

    LaunchedEffect(Unit) {
        while (true) {
            viewModel.refreshState()
            kotlinx.coroutines.delay(2000)
        }
    }

    // Nicht-Host-Spieler werden automatisch weitergeleitet, sobald der Host startet
    LaunchedEffect(uiState.gameState?.status) {
        if (uiState.gameState?.status == "running") {
            onGameStarted()
        }
    }
}