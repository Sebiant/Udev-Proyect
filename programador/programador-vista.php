<!-- Estilos CSS para mejorar el diseño -->
<style>
.materias-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
    padding: 20px;
}

.materia-card {
    width: 200px;
    height: 200px;
    border: 1px solid #ddd;
    border-radius: 16px;
    background: linear-gradient(to bottom right, #ffffff, #f0f4f8);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    text-align: center;
    padding: 20px;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.materia-card:hover {
    background: linear-gradient(to bottom right, #e8f0fe, #dbe9ff);
    transform: scale(1.05);
    box-shadow: 0 6px 16px rgba(0, 123, 255, 0.3);
}

.materia-card.seleccionada {
    border: 2px solid #007bff;
    background: linear-gradient(to bottom right, #d6e9ff, #c2ddff);
    transform: scale(1.08);
    box-shadow: 0 6px 18px rgba(0, 123, 255, 0.4);
}

.icono {
    font-size: 42px;
    margin-bottom: 10px;
    color: #007bff;
}
</style>


<?php
include_once '../componentes/header.php';
include '../conexion.php';

$sql_docentes = "SELECT numero_documento, nombres, apellidos FROM docentes";
$result_docentes = $conn->query($sql_docentes);

$sql_salones = "SELECT id_salon, nombre_salon FROM salones";
$result_salones = $conn->query($sql_salones);

$sql_periodos = "SELECT id_periodo, nombre FROM periodos";
$result_periodos = $conn->query($sql_periodos);

$sql_programas = "
    SELECT DISTINCT p.id_programa, p.nombre
    FROM modulos m
    JOIN programas p ON m.id_programa = p.id_programa
";
$result_programas = $conn->query($sql_programas);

?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h2>Programación de Clases</h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">Selección de Datos</div>
                        <div class="card-body">
                            <form id="formProgramador">
                                <div class="form-group">

                                    <div class="form-group">
                                        <label for="periodo">Periodo</label>
                                        <select id="periodo" name="periodo" class="form-control">
                                            <option value="">Seleccione Periodo</option>
                                            <?php while ($row = $result_periodos->fetch_assoc()): ?>
                                                <option value="<?php echo $row['id_periodo']; ?>"><?php echo $row['nombre']; ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="programa">Programa</label>
                                        <select id="programa" name="programa" class="form-control">
                                            <option value="">Seleccione un Programa</option>
                                            <?php
                                            if ($result_programas->num_rows > 0) {
                                                while ($row = $result_programas->fetch_assoc()) {
                                                    echo "<option value='" . htmlspecialchars($row['id_programa']) . "'>" . htmlspecialchars($row['nombre']) . "</option>";
                                                }
                                            } else {
                                                echo "<option value=''>No hay programas disponibles</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <br>
                                    <div class="modulos-container" id="modulosContainer">
                                        <!-- Aquí se llenarán dinámicamente las tarjetas -->
                                    </div>

                                    <input type="hidden" name="modulo" id="moduloSeleccionado">

                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">Programación de Horario y Modalidad</div>
                        <div class="card-body">

                            <h5 class="mb-3">Docente y Salon</h5>
                            <div class="form-group">
                                <label for="docente">Docente</label>
                                <select id="docente" name="docente" class="form-control">
                                    <option value="">Seleccione Docente</option>
                                    <?php while ($row = $result_docentes->fetch_assoc()): ?>
                                        <option value="<?php echo $row['numero_documento']; ?>">
                                            <?php echo $row['nombres'] . " " . $row['apellidos']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="salon">Salón</label>
                                <select id="salon" name="salon" class="form-control">
                                    <option value="">Seleccione Salón</option>
                                    <?php while ($row = $result_salones->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id_salon']; ?>">
                                            <?php echo $row['nombre_salon']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <hr>

                            <h5 class="mb-3">Horario</h5>
                            <div class="form-group">
                                <label for="dia">Día de la Semana</label>
                                <select id="dia" name="dia" class="form-control">
                                    <option value="">Seleccione Día</option>
                                    <option value="lunes">Lunes</option>
                                    <option value="martes">Martes</option>
                                    <option value="miercoles">Miércoles</option>
                                    <option value="jueves">Jueves</option>
                                    <option value="viernes">Viernes</option>
                                    <option value="sabado">Sábado</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="horaEntrada">Hora de Entrada</label>
                                <input type="time" id="horaEntrada" name="horaEntrada" class="form-control">
                            </div>

                            <div class="form-group">
                                <label for="horaSalida">Hora de Salida</label>
                                <input type="time" id="horaSalida" name="horaSalida" class="form-control">
                            </div>

                            <hr>

                            <h5 class="mb-3">Modalidad</h5>
                            <div class="form-group">
                                <label for="modalidad">Modalidad</label>
                                <select id="modalidad" name="modalidad" class="form-control">
                                    <option value="presencial">Presencial</option>
                                    <option value="virtual">Virtual</option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
            </form>
        </div>
        <div class="card-footer text-center">
            <button type="button" onclick="ProgramarClase()" class="btn btn-primary">Programar Clase</button>
        </div>
    </div>
</div>

<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h5>Clases Programadas</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="datos_programador" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora de Inicio</th>
                            <th>Hora de Salida</th>
                            <th>Salón</th>
                            <th>Docente</th>
                            <th>Materia</th>
                            <th>Modalidad</th>
                            <th>Estado</th>
                            <th>Modificar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Aquí irían las filas de la tabla -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Modal Reprogramación-->
<div class="modal fade" id="modalReprogramar" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reprogramar Clase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formReprogramar">
                    <input type="hidden" name="id_programador" id="id_programador">
                    <input type="hidden" name="numero_documento" id="numero_documento">
                    <input type="hidden" name="id_salon" id="id_salon">
                    <input type="hidden" name="id_modulo" id="id_modulo">
                    <input type="hidden" name="id_periodo" id="id_periodo">
                    <input type="hidden" name="modalidad" id="m">
                    

                    <label>Fecha:</label>
                    <input type="date" name="nueva_fecha" class="form-control" required>

                    <label>Hora Inicio:</label>
                    <input type="time" name="nueva_hora_inicio" class="form-control" required>

                    <label>Hora Salida:</label>
                    <input type="time" name="nueva_hora_salida" class="form-control" required>

                    <button type="button" class="btn btn-primary mt-3" onclick="reprogramarClase()">Guardar cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Modal de edición -->
<div class="modal fade" id="modalEditarClase" tabindex="-1" aria-labelledby="modalEditarClaseLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modificar Clase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editarClaseForm">

                    <input type="hidden" id="id_programador" name="id_programador">

                    <div class="mb-3">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="hora_inicio">Hora de Inicio</label>
                        <input type="time" id="hora_inicio" name="hora_inicio" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="hora_salida">Hora de Salida</label>
                        <input type="time" id="hora_salida" name="hora_salida" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="id_salon">Salón</label>
                        <select id="id_salon" name="id_salon" class="form-control">
                            <option value="">-- Selecciona un salón --</option>
                            <?php
                            $sql_salones = "SELECT id_salon, nombre_salon FROM salones";
                            $result_salones = $conn->query($sql_salones);

                            if ($result_salones->num_rows > 0) {
                                while ($row_salon = $result_salones->fetch_assoc()) {
                                    echo '<option value="' . $row_salon['id_salon'] . '">' . $row_salon['nombre_salon'] . '</option>';
                                }
                            } else {
                                echo '<option value="">No hay salones disponibles</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="numero_documento">Docente</label>
                        <select id="numero_documento" name="numero_documento" class="form-control">
                            <option value="">-- Selecciona un docente --</option>
                            <?php
                            $sql_docentes = "SELECT numero_documento, nombres, apellidos FROM docentes";
                            $result_docentes = $conn->query($sql_docentes);

                            if ($result_docentes->num_rows > 0) {
                                while ($row_docente = $result_docentes->fetch_assoc()) {
                                    echo '<option value="' . $row_docente['numero_documento'] . '">' . $row_docente['nombres'] . " " . $row_docente['apellidos'] . '</option>';
                                }
                            } else {
                                echo '<option value="">No hay docentes disponibles</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="id_modulo">Módulos</label>
                        <select id="id_modulo" name="id_modulo" class="form-control">
                            <option value="">-- Selecciona un módulo --</option>
                            <?php
                            $sql_materias = "SELECT id_modulo, nombre FROM modulos";
                            $result_materias = $conn->query($sql_materias);

                            if ($result_materias->num_rows > 0) {
                                while ($row_materias = $result_materias->fetch_assoc()) {
                                    echo '<option value="' . $row_materias['id_modulo'] . '">' . $row_materias['nombre'] . '</option>';
                                }
                            } else {
                                echo '<option value="">No hay módulos disponibles</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="modalidad">Modalidad</label>
                        <select name="modalidad" id="modalidad" class="form-control">
                            <option value="">-- Selecciona la Modalidad --</option>
                            <option value="presencial">Presencial</option>
                            <option value="virtual">Virtual</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="GuardarClase()">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>
<br>

<?php
include_once '../componentes/footer.php';
?>

<script src="js/Datatable-Programador.js"></script>

<script>

    function ProgramarClase() {
        const form = document.getElementById("formProgramador"); 
        const formData = new FormData(form);

        console.log("Datos del formulario:");
        for (const [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }
        $.ajax({
            url: 'Programador-Controlador.php?accion=crear',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                location.reload();
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
            }
        });
    }

    function GuardarClase() {
        const formData = new FormData(document.getElementById('editarClaseForm'));

        console.log('Datos del formulario:', ...formData.entries());

        $.ajax({
            url: 'Programador-Controlador.php?accion=editar',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Respuesta del servidor:', response);
                location.reload();
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
            }
        });
    }
</script>

<!-- Script para manejar la selección -->
<script>
function seleccionarMateria(idMateria) {
    // Remueve la clase seleccionada de todas las tarjetas
    document.querySelectorAll('.materia-card').forEach(card => {
        card.classList.remove('seleccionada');
    });

    // Agrega la clase seleccionada a la tarjeta clickeada
    document.getElementById(`materia_${idMateria}`).classList.add('seleccionada');

    // Asigna el valor al input oculto
    document.getElementById('materiaSeleccionada').value = idMateria;
}
</script>

<script>
    function reprogramarClase() {
    const formData = new FormData(document.getElementById('formReprogramar'));
    console.log(...formData);

    $.ajax({
        url: "Programador-Controlador.php?accion=reprogramar",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log("Respuesta del servidor:", response);
            location.reload();
        },
        error: function(xhr, status, error) {
            console.error("Error:", error);
            alert("Hubo un problema al procesar la solicitud.");
        }
    });
}
</script>
<script>
    document.getElementById("programa").addEventListener("change", function () {
        let idPrograma = this.value;

        if (idPrograma === "") {
            document.getElementById("modulosContainer").innerHTML = "<p>Seleccione un programa primero.</p>";
            return;
        }

        fetch("Programador-Controlador.php?accion=buscarMateriaPorPrograma", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "id_programa=" + encodeURIComponent(idPrograma)
        })
        .then(response => response.json())
        .then(data => {
            let container = document.getElementById("modulosContainer");
            container.innerHTML = "";

            if (data.status === "success" && data.modulos.length > 0) {
                data.modulos.forEach(modulo => {
                    let card = document.createElement("div");
                    card.className = "materia-card";
                    card.id = "modulo_" + modulo.id;
                    card.innerHTML = `
                        <div class="icono">📚</div>
                        <h6>${modulo.nombre}</h6>
                        <p class="fs-7"></p>
                    `;
                    card.onclick = function () {
                        seleccionarModulo(modulo.id);
                    };
                    container.appendChild(card);
                });
            } else {
                container.innerHTML = "<p>No hay módulos disponibles para este programa.</p>";
            }
        })
        .catch(error => {
            console.error("Error al cargar módulos:", error);
            document.getElementById("modulosContainer").innerHTML = "<p>Error al cargar módulos.</p>";
        });
    });

    // Esta función se llama al hacer clic en una tarjeta
    function seleccionarModulo(id) {
        document.getElementById("moduloSeleccionado").value = id;

        // Quitar selección previa
        document.querySelectorAll(".modulo-card").forEach(card => {
            card.classList.remove("seleccionada");
        });

        // Marcar como seleccionada
        let cardSeleccionada = document.getElementById("modulo_" + id);
        if (cardSeleccionada) {
            cardSeleccionada.classList.add("seleccionada");
        }
    }
</script>
