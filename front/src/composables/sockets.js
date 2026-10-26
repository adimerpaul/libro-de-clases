import { io } from 'socket.io-client'

// Servidor Socket.IO compartido (proyecto socket/): Laravel le hace POST /notify y él
// reemite el evento a todos. Local http://localhost:3000, producción saventura.tuprogam.com.
export const URL_SOCKET = import.meta.env.VITE_SOCKETS || 'http://localhost:3000'

// Debe coincidir con SOCKET_EVENTO_RFID del back (config/services.php)
export const EVENTO_RFID = 'libro-clases-rfid'

/** Socket.IO; se conecta al llamar a .connect(). */
export function crearSocket () {
  return io(URL_SOCKET, { autoConnect: false })
}
