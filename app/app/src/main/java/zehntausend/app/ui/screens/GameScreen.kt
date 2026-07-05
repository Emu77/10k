package zehntausend.app.ui.screens

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
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
fun GameScreen(
    viewModel: GameViewModel,
    onGameOver: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    val gameState = uiState.gameState
    val isMyTurn = gameState?.my_turn == true

    Column(
        modifier = Modifier.fillMaxSize().padding(16.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        // Punktestand
        Card(modifier = Modifier.fillMaxWidth()) {
            Column(modifier = Modifier.padding(12.dp)) {
                Text("Punktestand", fontWeight = FontWeight.Bold)
                Spacer(modifier = Modifier.height(4.dp))
                gameState?.players?.forEach { player ->
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        Text(
                            if (player.player_id == gameState.current_player_id) "▶ ${player.name}" else player.name
                        )
                        Text("${player.total_score} Pkt")
                    }
                }
            }
        }

        Spacer(modifier = Modifier.height(16.dp))

        // Status
        Text(
            gameState?.message ?: "Warte...",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.secondary
        )

        Spacer(modifier = Modifier.height(8.dp))
        Text("Rundenstand: ${gameState?.turn_score ?: 0} Pkt", fontWeight = FontWeight.SemiBold)

        Spacer(modifier = Modifier.height(16.dp))

        // Würfel
        val dice = gameState?.rolled?.takeIf { it.isNotEmpty() } ?: gameState?.dice ?: emptyList()
        val kept = gameState?.kept ?: emptyList()

        Text("Würfel:", fontWeight = FontWeight.SemiBold)
        Spacer(modifier = Modifier.height(8.dp))

        LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            itemsIndexed(dice) { index, value ->
                val isSelected = uiState.selectedDice.contains(index)
                OutlinedCard(
                    onClick = { if (isMyTurn) viewModel.toggleDie(index) },
                    border = BorderStroke(
                        2.dp,
                        if (isSelected) MaterialTheme.colorScheme.primary
                        else MaterialTheme.colorScheme.outline
                    ),
                    colors = CardDefaults.outlinedCardColors(
                        containerColor = if (isSelected)
                            MaterialTheme.colorScheme.primaryContainer
                        else MaterialTheme.colorScheme.surface
                    )
                ) {
                    Box(
                        modifier = Modifier.size(64.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        Text(
                            diceFace(value),
                            fontSize = 32.sp
                        )
                    }
                }
            }
        }

        if (kept.isNotEmpty()) {
            Spacer(modifier = Modifier.height(12.dp))
            Text("Behalten:", fontWeight = FontWeight.SemiBold)
            Spacer(modifier = Modifier.height(4.dp))
            LazyRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                items(kept.size) { index ->
                    Card(colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.tertiaryContainer)) {
                        Box(modifier = Modifier.size(48.dp), contentAlignment = Alignment.Center) {
                            Text(diceFace(kept[index]), fontSize = 24.sp)
                        }
                    }
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
        } else if (isMyTurn) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Button(
                    onClick = { viewModel.rollDice() },
                    modifier = Modifier.weight(1f)
                ) { Text("Würfeln") }

                if (uiState.selectedDice.isNotEmpty()) {
                    Button(
                        onClick = { viewModel.keepDice() },
                        modifier = Modifier.weight(1f)
                    ) { Text("Behalten") }
                }

                OutlinedButton(
                    onClick = { viewModel.bank { if (gameState?.status == "finished") onGameOver() } },
                    modifier = Modifier.weight(1f)
                ) { Text("Einbanken") }
            }
        } else {
            OutlinedButton(
                onClick = { viewModel.refreshState() },
                modifier = Modifier.fillMaxWidth()
            ) { Text("Aktualisieren") }
        }
    }
    LaunchedEffect(gameState?.current_player_id) {
        while (true) {
            kotlinx.coroutines.delay(2000)
            viewModel.refreshState()
            val state = viewModel.uiState.value.gameState
            val currentPlayer = state?.players?.find { it.player_id == state.current_player_id }
            if (currentPlayer?.is_ai == 1) {
                viewModel.triggerAiTurn()
            }
        }
    }
}

fun diceFace(value: Int): String = when (value) {
    1 -> "⚀"; 2 -> "⚁"; 3 -> "⚂"; 4 -> "⚃"; 5 -> "⚄"; 6 -> "⚅"; else -> "?"
}
