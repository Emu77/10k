package zehntausend.app.ui.screens

import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import zehntausend.app.viewmodel.GameViewModel

@Composable
fun ResultScreen(
    viewModel: GameViewModel,
    onPlayAgain: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val players = uiState.gameState?.players
        ?.sortedWith(
            compareBy<zehntausend.app.data.model.Player, Int?>(nullsLast()) { it.finish_rank }
                .thenByDescending { it.total_score }
        ) ?: emptyList()
    val winner = players.firstOrNull()

    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Text("🏆 Spiel beendet!", fontSize = 28.sp, fontWeight = FontWeight.Bold)
        Spacer(modifier = Modifier.height(8.dp))

        winner?.let {
            Text("Gewinner: ${it.name}", fontSize = 22.sp, color = MaterialTheme.colorScheme.primary)
            Text("${it.total_score} Punkte", fontSize = 18.sp)
        }

        Spacer(modifier = Modifier.height(32.dp))
        Text("Ergebnisse:", fontWeight = FontWeight.SemiBold)
        Spacer(modifier = Modifier.height(8.dp))

        LazyColumn(modifier = Modifier.fillMaxWidth()) {
            itemsIndexed(players) { index, player ->
                ListItem(
                    headlineContent = { Text("${index + 1}. ${player.name}") },
                    trailingContent = { Text("${player.total_score} Pkt", fontWeight = FontWeight.Bold) }
                )
                HorizontalDivider()
            }
        }

        Spacer(modifier = Modifier.height(32.dp))

        Button(
            onClick = onPlayAgain,
            modifier = Modifier.fillMaxWidth()
        ) { Text("Neues Spiel") }
    }
}
