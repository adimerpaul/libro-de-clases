<template>
  <q-page class="libroPie">
    <!-- Selección de curso -->
    <template v-if="!cursoId">
      <div class="roleBanner">
        <div>
          <h2>Libro de Clases PIE</h2>
          <p>Programa de Integración Escolar · Educación Especial Diferencial. Elige el curso para abrir su libro: la nómina son los estudiantes con caso PIE del año.</p>
        </div>
        <q-select v-model="anio" :options="anios" dense outlined label="Año escolar" style="width: 130px" bg-color="white" @update:model-value="cargarCursos" />
      </div>
      <div v-if="cargandoCursos" class="cursosGrid">
        <q-skeleton v-for="i in 8" :key="i" height="96px" style="border-radius: 14px" />
      </div>
      <div v-else-if="!cursos.length" class="empty">No hay cursos en {{ anio }}.</div>
      <div v-else class="cursosGrid">
        <button v-for="c in cursos" :key="c.id" class="cursoCard" :class="{ vacio: !c.pie_count }" @click="abrir(c.id)">
          <q-icon name="fas fa-book-open" class="cursoIcon" />
          <b>{{ c.nombre }}</b>
          <small>{{ c.pie_count ? `${c.pie_count} estudiante${c.pie_count === 1 ? '' : 's'} PIE` : 'Sin estudiantes PIE' }}</small>
          <small v-if="c.profesor_jefe" class="jefe">Prof. jefe: {{ c.profesor_jefe.name }}</small>
        </button>
      </div>
    </template>

    <!-- El libro -->
    <div v-else class="wrap">
      <header class="bar">
        <q-btn flat round dense icon="fas fa-arrow-left" aria-label="Volver a los cursos" @click="cerrar" />
        <h1>Libro de Clases PIE<small>{{ libro ? `${libro.curso.nombre} ${libro.curso.anio} · ` : '' }}Programa de Integración Escolar · Educación Especial Diferencial</small></h1>
        <div class="grow" />
        <span class="chip" :class="sync.clase"><i />{{ sync.texto }}</span>
        <div class="nav">
          <button class="btn" :disabled="actual <= 0" aria-label="Página anterior" @click="ir(actual - 1)">‹ Anterior</button>
          <span class="pg">{{ actual === 0 ? 'Portada' : `Pág. ${actual} de ${PAGES.length - 1}` }}</span>
          <button class="btn" :disabled="actual >= PAGES.length - 1" aria-label="Página siguiente" @click="ir(actual + 1)">Siguiente ›</button>
        </div>
      </header>

      <div class="desk">
        <nav class="ribbons" aria-label="Separadores del libro">
          <button
            v-for="(p, i) in PAGES" :key="p.id" class="ribbon" :style="{ '--c': p.c }" :aria-current="i === actual"
            @click="ir(i)"
          >
            {{ p.s }}
          </button>
        </nav>
        <div class="book">
          <div class="sheet">
            <div v-if="!listo" class="hoja"><div class="loading">Abriendo el libro…</div></div>
            <transition v-else :name="direccion > 0 ? 'pasa' : 'vuelve'" mode="out-in">
              <section :key="actual" class="hoja" :class="{ cover: pg.id === 'portada' }" :aria-label="pg.t">
                <!-- ───── Portada ───── -->
                <template v-if="pg.id === 'portada'">
                  <div class="cloud" style="width:120px;height:42px;left:58%;top:12%" />
                  <div class="cloud" style="width:90px;height:32px;left:80%;top:30%" />
                  <div class="cloud" style="width:70px;height:26px;left:12%;top:70%;opacity:.7" />
                  <div class="hill2" /><div class="hill" /><div class="grassdots" />
                  <div class="insignia" aria-hidden="true">
                    <svg viewBox="0 0 320 320" role="img">
                      <defs><path id="arcoPie" d="M 34 172 A 126 126 0 0 1 286 172" /></defs>
                      <circle cx="160" cy="160" r="156" fill="#4f9d3a" stroke="#fff" stroke-width="3" stroke-dasharray="4 5" />
                      <circle cx="160" cy="160" r="138" fill="#5fb246" />
                      <text font-family="Baloo 2, sans-serif" font-weight="800" font-size="22" fill="#fff" letter-spacing="3"><textPath href="#arcoPie" startOffset="50%" text-anchor="middle">EDUCACIÓN INCLUSIVA</textPath></text>
                      <circle cx="160" cy="164" r="70" fill="#fff6c8" />
                      <g font-family="Baloo 2, sans-serif" font-weight="800" text-anchor="middle">
                        <rect x="104" y="128" width="34" height="34" rx="7" fill="#e85a4f" /><text x="121" y="154" font-size="24" fill="#fff">A</text>
                        <rect x="143" y="120" width="34" height="34" rx="7" fill="#3ea6e6" /><text x="160" y="146" font-size="24" fill="#fff">B</text>
                        <rect x="182" y="128" width="34" height="34" rx="7" fill="#f5b72c" /><text x="199" y="154" font-size="24" fill="#fff">C</text>
                        <text x="160" y="200" font-size="26" fill="#1b2a52">1 + 2 = 3</text>
                        <text x="160" y="226" font-size="13" fill="#4f7a2a" letter-spacing="2">P · I · E</text>
                      </g>
                      <g v-for="ic in ICONOS" :key="ic.t" :transform="`translate(${ic.x} ${ic.y})`">
                        <circle r="13" fill="#ffffff" opacity=".9" /><circle r="5" fill="#2f7d2a" />
                        <text y="-17" text-anchor="middle" font-size="8" fill="#fff" letter-spacing=".5">{{ ic.t.toUpperCase() }}</text>
                      </g>
                    </svg>
                  </div>
                  <div class="label">
                    <div class="t">Libro<br>de<br>Clases</div>
                    <div class="sub"><b>PIE</b>Programa de Integración Escolar<br>Educación Especial Diferencial</div>
                    <div class="slots">
                      <div class="slot"><span>Educador(a)</span><b>{{ gen.educador }}</b></div>
                      <div class="slot"><span>Grupo / Curso</span><b>{{ libro.curso.nombre }}</b></div>
                      <div class="slot"><span>Nivel</span><b>{{ libro.curso.nivel }}</b></div>
                      <div class="slot"><span>Año</span><b>{{ libro.curso.anio }}</b></div>
                    </div>
                    <button class="open" @click="irA('contenido')">Abrir libro →</button>
                  </div>
                  <div class="code">Edición digital 2.0</div>
                </template>

                <!-- ───── 1. Información general ───── -->
                <template v-else-if="pg.id === 'general'">
                  <div class="band"><span class="n">1</span><h2>{{ pg.t }}</h2></div>
                  <div class="form">
                    <label class="field"><span>Año</span><input :value="libro.curso.anio" disabled></label>
                    <label class="field"><span>Ciclo</span><input v-model="gen.ciclo" :disabled="!puede" @input="guardar('general')"></label>
                    <label class="field"><span>Curso</span><input :value="libro.curso.nombre" disabled></label>
                    <label class="field"><span>Nivel</span><input :value="libro.curso.nivel" disabled></label>
                    <label v-for="f in CAMPOS_GENERAL" :key="f[0]" class="field" :class="{ full: f[2] }">
                      <span>{{ f[1] }}</span><input v-model="gen[f[0]]" :disabled="!puede" autocomplete="off" @input="guardar('general')">
                    </label>
                  </div>
                  <div class="toolrow"><span class="hint grow">Año, curso y nivel vienen del curso. Lo demás se guarda solo mientras escribes y completa la portada.</span></div>
                </template>

                <!-- ───── Índice ───── -->
                <template v-else-if="pg.id === 'contenido'">
                  <div class="band"><h2>{{ pg.t }}</h2></div>
                  <p class="lead">El mismo índice del libro impreso. Cada línea te lleva a su página.</p>
                  <ol class="toc">
                    <li v-for="(t, i) in TOC" :key="i">
                      <button @click="irA(t[2])"><span class="no">{{ t[0] ? t[0] + '.' : '' }}</span><span>{{ t[1] }}</span><span class="dots" /><span class="go">ir →</span></button>
                    </li>
                    <li><button @click="irA('resumen')"><span class="no">+</span><span>Resumen del curso</span><span class="tag">Nuevo</span><span class="dots" /><span class="go">ir →</span></button></li>
                  </ol>
                </template>

                <!-- ───── Resumen ───── -->
                <template v-else-if="pg.id === 'resumen'">
                  <div class="band"><h2>{{ pg.t }}</h2><span class="tag">Nuevo en 2.0</span></div>
                  <p class="lead">Lo que el libro impreso obligaba a contar a mano, calculado al día. Asistencia de {{ MESES[resumen.mes - 1].toLowerCase() }} {{ anioLibro }}.</p>
                  <div class="stats">
                    <div class="stat">
                      <div class="k">Matrícula PIE</div><div class="v">{{ alumnos.length }}</div>
                      <div class="s">{{ resumen.H }} hombres · {{ resumen.M }} mujeres</div>
                      <div class="bars"><span :style="{ width: pct(resumen.H) + '%', background: 'var(--info)' }" /><span :style="{ width: pct(resumen.M) + '%', background: 'var(--lime)' }" /></div>
                    </div>
                    <div class="stat">
                      <div class="k">NEE</div><div class="v">{{ resumen.perm }}<span style="font-size:18px;color:var(--muted)"> / {{ resumen.trans }}</span></div>
                      <div class="s">permanentes / transitorias</div>
                      <div class="bars"><span :style="{ width: pct(resumen.perm) + '%', background: 'var(--brand)' }" /><span :style="{ width: pct(resumen.trans) + '%', background: 'var(--warn)' }" /></div>
                    </div>
                    <div class="stat">
                      <div class="k">Asistencia {{ MESES[resumen.mes - 1] }}</div>
                      <div class="v" :style="{ color: resumen.avg != null && resumen.avg < 85 ? 'var(--bad)' : 'inherit' }">{{ resumen.avg == null ? '—' : resumen.avg + '%' }}</div>
                      <div class="s">{{ resumen.avg == null ? 'Aún sin registros este mes' : 'promedio del grupo' }}</div>
                      <div class="bars"><span :style="{ width: (resumen.avg || 0) + '%', background: resumen.avg != null && resumen.avg < 85 ? 'var(--bad)' : 'var(--ok)' }" /></div>
                    </div>
                    <div class="stat">
                      <div class="k">Clases registradas</div><div class="v">{{ resumen.clases }}</div>
                      <div class="s">{{ resumen.entrevistas }} entrevista{{ resumen.entrevistas === 1 ? '' : 's' }} con apoderados</div>
                    </div>
                  </div>
                  <div class="cols2">
                    <div class="sec">
                      <h3>Alerta de asistencia <span class="n">bajo 85%</span></h3>
                      <div class="list">
                        <div v-for="[s, st] in resumen.bajos" :key="s.id" class="fila">
                          <span class="grow">{{ nombre(s) }}</span><span class="pill bad">{{ st.pct }}%</span>
                          <button class="btn chico ghost" @click="verHoja(s.id)">Hoja de vida</button>
                        </div>
                        <div v-if="!resumen.bajos.length" class="empty">{{ resumen.cnt ? 'Todos los estudiantes están sobre 85% este mes.' : 'Registra asistencia para ver alertas.' }}</div>
                      </div>
                    </div>
                    <div class="sec">
                      <h3>Diagnósticos del grupo</h3>
                      <div class="list">
                        <div v-for="[k, v] in resumen.diag" :key="k" class="fila"><span class="grow">{{ k }}</span><span class="pill info">{{ v }}</span></div>
                        <div v-if="!alumnos.length" class="empty"><SinAlumnos /></div>
                      </div>
                    </div>
                  </div>
                </template>

                <!-- ───── 2. Registro de matrícula ───── -->
                <template v-else-if="pg.id === 'matricula'">
                  <div class="band"><span class="n">2</span><h2>{{ pg.t }}</h2></div>
                  <p class="lead">Antecedentes generales del (la) alumno(a). Vienen de la ficha del estudiante y de su caso PIE; la edad al 31 de marzo se calcula sola.</p>
                  <div class="scroll">
                    <table class="grid">
                      <thead>
                        <tr>
                          <th>N°</th><th>Apellido paterno</th><th>Apellido materno</th><th>Nombres</th><th>RUN</th><th>Sexo</th><th>Fecha de nacimiento</th>
                          <th>Edad al 31 de marzo</th><th>Fecha de matrícula</th><th>Domicilio · Dirección</th><th>Comuna</th><th>NEE</th><th>Diagnóstico</th><th>Observaciones</th><th />
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(s, i) in alumnos" :key="s.id">
                          <td class="num">{{ i + 1 }}</td>
                          <td class="ro">{{ s.apPat }}</td><td class="ro">{{ s.apMat }}</td><td class="ro">{{ s.nombres }}</td>
                          <td class="ro">{{ formatoRun(s.run) }}</td><td class="ro center">{{ SEXO[s.sexo] || '' }}</td>
                          <td class="ro">{{ fecha(s.fechaNac) }}</td><td class="ro center">{{ edad31Marzo(s.fechaNac) }}</td>
                          <td class="ro">{{ fecha(s.fechaMatricula) }}</td><td class="ro">{{ s.direccion }}</td>
                          <td><input v-model="extra(s.id).comuna" :disabled="!puede" style="min-width:110px" @input="guardar('matricula')"></td>
                          <td class="ro">{{ s.nee }}</td><td class="ro">{{ s.diagnostico }}</td>
                          <td><input v-model="extra(s.id).obs" :disabled="!puede" style="min-width:170px" @input="guardar('matricula')"></td>
                          <td><router-link class="del" :to="`/pie/${s.caso_id}`" title="Abrir caso PIE"><q-icon name="fas fa-arrow-up-right-from-square" size="12px" /></router-link></td>
                        </tr>
                        <tr v-if="!alumnos.length"><td colspan="15"><div class="empty"><SinAlumnos /></div></td></tr>
                      </tbody>
                    </table>
                  </div>
                  <div class="toolrow"><span class="hint">{{ alumnos.length }} estudiante{{ alumnos.length === 1 ? '' : 's' }} PIE en el curso. Para agregar o egresar, usa <router-link to="/pie">PIE / Inclusión</router-link>.</span></div>
                </template>

                <!-- ───── 3. Antecedentes familiares ───── -->
                <template v-else-if="pg.id === 'familia'">
                  <div class="band"><span class="n">3</span><h2>{{ pg.t }}</h2></div>
                  <p class="lead">Datos de contacto y grupo familiar. Los nombres vienen del registro de matrícula; el apoderado parte con el de la ficha del estudiante.</p>
                  <div class="scroll">
                    <table class="grid">
                      <thead><tr><th>N°</th><th>Alumno(a)</th><th v-for="c in COLS_FAMILIA" :key="c[0]">{{ c[1] }}</th></tr></thead>
                      <tbody>
                        <tr v-for="(s, i) in alumnos" :key="s.id">
                          <td class="num">{{ i + 1 }}</td><td class="ro">{{ nombre(s) }}</td>
                          <td v-for="c in COLS_FAMILIA" :key="c[0]">
                            <input v-model="fam(s)[c[0]]" :disabled="!puede" :style="{ minWidth: c[2] + 'px' }" @input="guardar('familia')">
                          </td>
                        </tr>
                        <tr v-if="!alumnos.length"><td colspan="9"><div class="empty"><SinAlumnos /></div></td></tr>
                      </tbody>
                    </table>
                  </div>
                </template>

                <!-- ───── 4. Asistencia diaria ───── -->
                <template v-else-if="pg.id === 'asistencia'">
                  <div class="band">
                    <span class="n">4</span><h2>{{ pg.t }}</h2><span class="grow" />
                    <label class="inl">Mes de
                      <select v-model.number="ui.mes" @change="prepararPagina('asistencia')">
                        <option v-for="m in MESES_LECTIVOS" :key="m" :value="m">{{ MESES[m - 1] }}</option>
                      </select>
                    </label>
                    <span class="chip">de {{ anioLibro }}</span>
                    <span class="chip">Días trabajados: <b>{{ asis.trabajados.length }}</b></span>
                  </div>
                  <p class="lead">Toca una casilla para cambiar entre presente, ausente, justificado y atraso. Toca el número del día para marcar a todo el curso presente.</p>
                  <div class="scroll">
                    <table class="grid att">
                      <thead>
                        <tr>
                          <th>N°</th><th class="name">Apellidos y nombres</th>
                          <th
                            v-for="d in asis.dias" :key="d.d" class="day" :class="{ we: d.we }"
                            :title="d.we ? '' : `Marcar a todos presentes el ${d.d}`" @click="!d.we && todosPresentes(d.d)"
                          >
                            {{ d.d }}<small>{{ d.dia }}</small>
                          </th>
                          <th>Asist.</th><th>Inasist.</th><th>%</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(s, i) in alumnos" :key="s.id">
                          <td class="num">{{ i + 1 }}</td><td class="name">{{ nombre(s) }}</td>
                          <template v-for="d in asis.dias" :key="d.d">
                            <td v-if="d.we" class="c we" />
                            <td v-else class="c">
                              <button
                                class="mk" :data-v="marca(s.id, d.d)" :disabled="!puede"
                                :aria-label="`${nombre(s)}, día ${d.d}: ${ESTADO_ASIS[marca(s.id, d.d)] || 'sin marcar'}`" @click="ciclar(s.id, d.d)"
                              >
                                {{ marca(s.id, d.d) === 'P' ? '•' : marca(s.id, d.d) }}
                              </button>
                            </td>
                          </template>
                          <td class="ro center">{{ asis.stats[s.id].asist }}</td>
                          <td class="ro center">{{ asis.stats[s.id].inas }}</td>
                          <td class="ro center pct" :class="{ low: asis.stats[s.id].pct != null && asis.stats[s.id].pct < 85 }">{{ asis.stats[s.id].pct == null ? '—' : asis.stats[s.id].pct + '%' }}</td>
                        </tr>
                        <tr v-if="!alumnos.length"><td :colspan="asis.dias.length + 5"><div class="empty"><SinAlumnos /> para pasar lista.</div></td></tr>
                      </tbody>
                      <tfoot v-if="alumnos.length">
                        <tr v-for="f in PIE_ASIS" :key="f[0]">
                          <td /><td class="lbl">{{ f[0] }}</td>
                          <template v-for="d in asis.dias" :key="d.d">
                            <td v-if="d.we" class="we" />
                            <td v-else>{{ asis.trabajados.includes(d.d) ? (f[1] ? contar(d.d, f[1]) : alumnos.length) : '' }}</td>
                          </template>
                          <td colspan="3" />
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                  <div class="legend">
                    <span><b style="color:var(--ok)">•</b>Presente</span>
                    <span><b style="background:var(--bad);color:var(--paper)">A</b>Ausente</span>
                    <span><b style="background:var(--warn);color:var(--paper)">J</b>Ausente justificado</span>
                    <span><b style="color:var(--info);background:color-mix(in srgb,var(--info) 15%,transparent)">R</b>Atraso (cuenta como presente)</span>
                    <span>En rojo: bajo 85% de asistencia</span>
                  </div>
                </template>

                <!-- ───── 6. Registro de actividades ───── -->
                <template v-else-if="pg.id === 'actividades'">
                  <div class="band">
                    <span class="n">6</span><h2>{{ pg.t }}</h2><span class="grow" />
                    <label class="inl">Fecha <input v-model="ui.fecha" type="date" @change="ui.fecha && prepararPagina('actividades')"></label>
                    <span class="chip">{{ act.titulo }}</span>
                  </div>
                  <div class="datechips" aria-label="Días con registro este mes">
                    <button v-for="x in act.dias" :key="x" :aria-pressed="x === ui.fecha" @click="ui.fecha = x; prepararPagina('actividades')">{{ Number(x.slice(8)) }}</button>
                  </div>
                  <div class="scroll">
                    <table class="grid">
                      <thead>
                        <tr>
                          <th style="width:80px">Hora o período</th><th>Materia · contenido · tema u objetivos</th><th>Actividades desarrolladas</th>
                          <th style="width:78px">Inasist.</th><th style="width:78px">Asist.</th><th style="width:78px">Atrasos</th><th style="width:150px">Evaluación<br>(tipo de instrumento)</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(r, i) in act.doc.rows" :key="i">
                          <td><input v-model="r.hora" :placeholder="`${i + 1}°`" :disabled="act.bloqueado" @input="guardar(act.clave)"></td>
                          <td><textarea v-model="r.materia" rows="1" style="min-width:200px" :disabled="act.bloqueado" @input="guardar(act.clave)" /></td>
                          <td><textarea v-model="r.actividades" rows="1" style="min-width:220px" :disabled="act.bloqueado" @input="guardar(act.clave)" /></td>
                          <td><input v-model="r.inasist" inputmode="numeric" :disabled="act.bloqueado" @input="guardar(act.clave)"></td>
                          <td><input v-model="r.asist" inputmode="numeric" :disabled="act.bloqueado" @input="guardar(act.clave)"></td>
                          <td><input v-model="r.atrasos" inputmode="numeric" :disabled="act.bloqueado" @input="guardar(act.clave)"></td>
                          <td><input v-model="r.eval" list="instrPie" style="min-width:140px" :disabled="act.bloqueado" @input="guardar(act.clave)"></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <datalist id="instrPie"><option v-for="o in INSTRUMENTOS" :key="o" :value="o" /></datalist>
                  <div class="toolrow"><button v-if="!act.bloqueado" class="btn chico" @click="act.doc.rows.push(filaVacia()); guardar(act.clave)">+ Agregar período</button><span class="grow" /></div>
                  <div class="actfoot">
                    <label style="display:grid;gap:4px">
                      <span class="hint" style="text-transform:uppercase;letter-spacing:1px;color:var(--brand);font-weight:700;font-size:11px">Observaciones</span>
                      <textarea v-model="act.doc.obs" class="lined" style="min-height:92px" :disabled="act.bloqueado" @input="guardar(act.clave)" />
                    </label>
                    <div class="hmt">
                      <div class="t">Asistencia diaria</div>
                      <div class="boxes"><div>H<b>{{ act.hay ? act.H : '–' }}</b></div><div>M<b>{{ act.hay ? act.M : '–' }}</b></div><div>T<b>{{ act.hay ? act.H + act.M : '–' }}</b></div></div>
                      <div class="note">{{ act.hay ? 'Tomada de la asistencia del día' : 'Pasa lista en Asistencia diaria' }}</div>
                    </div>
                    <div class="sign">
                      <template v-if="act.firma">
                        <div class="stamp"><b>Firmado</b>{{ act.firma.nombre }}<br>{{ fechaHora(act.firma.fecha) }}</div>
                        <button v-if="puede" class="btn chico ghost" @click="quitarFirma">Quitar firma para editar</button>
                      </template>
                      <template v-else>
                        <div class="obsline">{{ $auth.user?.name }}</div>
                        <button v-if="puede" class="btn chico primary" @click="firmar">Firmar registro del día</button>
                      </template>
                      <div class="line">Firma del profesor(a)</div>
                    </div>
                  </div>
                </template>

                <!-- ───── 8. Evaluaciones ───── -->
                <template v-else-if="pg.id === 'evaluaciones'">
                  <div class="band">
                    <span class="n">8</span><h2>{{ pg.t }}</h2><span class="grow" />
                    <label class="inl">Período
                      <select v-model="ui.periodo" @change="prepararPagina('evaluaciones')">
                        <option v-for="(v, k) in PERIODOS" :key="k" :value="k">{{ v }}</option>
                      </select>
                    </label>
                    <label class="inl">Fecha <input v-model="ev.fecha" type="date" :disabled="!puede" @input="guardar(evClave)"></label>
                  </div>
                  <p class="lead">Cinco áreas con cinco objetivos cada una, como en el libro. Toca una casilla para registrar el nivel de logro; la situación final se sugiere sola y puedes reemplazarla.</p>
                  <div class="scroll">
                    <table class="grid att">
                      <thead>
                        <tr>
                          <th rowspan="2">N°</th><th rowspan="2" class="name">Alumno(a)</th>
                          <th v-for="(a, ai) in ev.areas" :key="ai" colspan="5" class="areah">Área<input v-model="a.nombre" placeholder="Ej.: Lenguaje" :disabled="!puede" @input="guardar(evClave)"></th>
                          <th rowspan="2" style="min-width:170px">Situación final</th>
                        </tr>
                        <tr>
                          <template v-for="(a, ai) in ev.areas" :key="ai">
                            <th v-for="o in 5" :key="o" class="day" style="cursor:default">{{ o }}</th>
                          </template>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(s, i) in alumnos" :key="s.id">
                          <td class="num">{{ i + 1 }}</td><td class="name">{{ nombre(s) }}</td>
                          <template v-for="(a, ai) in ev.areas" :key="ai">
                            <td v-for="o in 5" :key="o" class="c">
                              <button
                                class="ev" :data-v="logro(s.id, ai, o - 1)" :disabled="!puede"
                                :aria-label="`${nombre(s)}, ${a.nombre || 'área ' + (ai + 1)}, objetivo ${o}: ${logro(s.id, ai, o - 1) || 'sin evaluar'}`"
                                @click="ciclarLogro(s.id, ai, o - 1)"
                              >
                                {{ logro(s.id, ai, o - 1) }}
                              </button>
                            </td>
                          </template>
                          <td>
                            <input v-model="ev.final[s.id]" :placeholder="sugerencia(s.id)" :disabled="!puede" @input="guardar(evClave)">
                            <span class="sug">{{ sugerencia(s.id) ? 'Sugerido: ' + sugerencia(s.id) : '' }}</span>
                          </td>
                        </tr>
                        <tr v-if="!alumnos.length"><td colspan="28"><div class="empty"><SinAlumnos /></div></td></tr>
                      </tbody>
                    </table>
                  </div>
                  <div class="legend">
                    <span><b class="ev" data-v="L" style="width:26px">L</b>Logrado</span>
                    <span><b class="ev" data-v="ML" style="width:26px">ML</b>Medianamente logrado</span>
                    <span><b class="ev" data-v="PL" style="width:26px">PL</b>Por lograr</span>
                    <span><b class="ev" data-v="NO" style="width:26px">NO</b>No observado</span>
                  </div>
                  <div class="objs">
                    <fieldset v-for="(a, ai) in ev.areas" :key="ai">
                      <legend>Objetivos · {{ a.nombre || 'Área ' + (ai + 1) }}</legend>
                      <label v-for="(o, oi) in a.obj" :key="oi"><b>{{ oi + 1 }}.-</b><input v-model="a.obj[oi]" :disabled="!puede" @input="guardar(evClave)"></label>
                    </fieldset>
                  </div>
                </template>

                <!-- ───── 10–15. Plan de trabajo anual ───── -->
                <template v-else-if="pg.id === 'plan'">
                  <div class="band"><span class="n">10</span><h2>{{ pg.t }}</h2></div>
                  <p class="lead">Elaboración del plan de trabajo anual del equipo PIE, con sus objetivos y los tres períodos del año.</p>
                  <div class="sec">
                    <h3><span class="n">10.</span>Elaboración del plan de trabajo anual</h3>
                    <textarea v-model="plan.elaboracion" class="lined" placeholder="Fundamentación, diagnóstico del grupo, estrategias de co-docencia…" :disabled="!puede" @input="guardar('plan')" />
                  </div>
                  <div class="cols2">
                    <div class="sec"><h3><span class="n">11.</span>Objetivos generales</h3><textarea v-model="plan.generales" class="lined" style="min-height:212px" :disabled="!puede" @input="guardar('plan')" /></div>
                    <div class="sec"><h3><span class="n">12.</span>Objetivos específicos</h3><textarea v-model="plan.especificos" class="lined" style="min-height:212px" :disabled="!puede" @input="guardar('plan')" /></div>
                  </div>
                  <div class="periods">
                    <div v-for="p in PERIODOS_PLAN" :key="p[0]" class="sec">
                      <h3><span class="n">{{ p[1] }}.</span>{{ p[2] }}</h3>
                      <div class="dates">
                        <label>Desde <input v-model="plan[p[0]].ini" type="date" :disabled="!puede" @input="guardar('plan')"></label>
                        <label>Hasta <input v-model="plan[p[0]].fin" type="date" :disabled="!puede" @input="guardar('plan')"></label>
                      </div>
                      <textarea v-model="plan[p[0]].txt" class="lined" placeholder="Acciones y responsables del período" :disabled="!puede" @input="guardar('plan')" />
                    </div>
                  </div>
                </template>

                <!-- ───── 16. Entrevistas ───── -->
                <template v-else-if="pg.id === 'entrevistas'">
                  <div class="band">
                    <span class="n">16</span><h2>{{ pg.t }}</h2><span class="grow" />
                    <button v-if="puede" class="btn chico primary" @click="nuevaEntrevista">+ Nueva entrevista</button>
                  </div>
                  <p class="lead">Cada entrevista queda como un acta: quién asistió, el motivo y los acuerdos tomados.</p>
                  <div class="stack">
                    <article v-for="r in entrevistasOrdenadas" :key="r.id" class="acta">
                      <div class="top">
                        <span class="pill" :class="r.tipo === 'Reunión de apoderados' ? 'info' : 'mute'">{{ r.tipo || 'Entrevista' }}</span><span class="grow" />
                        <button v-if="puede" class="del" @click="borrar('ent', r.id, () => { ent.list = ent.list.filter(x => x.id !== r.id); guardar('entrevistas') })">{{ seguro === 'ent' + r.id ? '¿Eliminar acta?' : 'Eliminar' }}</button>
                      </div>
                      <div class="g3">
                        <label>Fecha<input v-model="r.fecha" type="date" :disabled="!puede" @input="guardar('entrevistas')"></label>
                        <label>Alumno(a)
                          <select v-model="r.alumnoId" :disabled="!puede" @change="guardar('entrevistas')">
                            <option :value="null">— Seleccionar —</option>
                            <option v-for="s in alumnos" :key="s.id" :value="s.id">{{ nombre(s) }}</option>
                          </select>
                        </label>
                        <label>Tipo
                          <select v-model="r.tipo" :disabled="!puede" @change="guardar('entrevistas')">
                            <option v-for="o in TIPOS_ENTREVISTA" :key="o">{{ o }}</option>
                          </select>
                        </label>
                      </div>
                      <div class="g3">
                        <label>Asiste<input v-model="r.asistente" placeholder="Nombre del apoderado(a)" :disabled="!puede" @input="guardar('entrevistas')"></label>
                        <label>Parentesco<input v-model="r.parentesco" :disabled="!puede" @input="guardar('entrevistas')"></label>
                        <label>Profesional<input v-model="r.profesional" :disabled="!puede" @input="guardar('entrevistas')"></label>
                      </div>
                      <label>Motivo<textarea v-model="r.motivo" rows="2" :disabled="!puede" @input="guardar('entrevistas')" /></label>
                      <label>Acuerdos y compromisos<textarea v-model="r.acuerdos" rows="2" :disabled="!puede" @input="guardar('entrevistas')" /></label>
                    </article>
                    <div v-if="!ent.list.length" class="list"><div class="empty">No hay entrevistas registradas.{{ puede ? ' Crea la primera con “Nueva entrevista”.' : '' }}</div></div>
                  </div>
                </template>

                <!-- ───── 17. Hoja de vida ───── -->
                <template v-else-if="pg.id === 'hojas'">
                  <div class="band"><span class="n">17</span><h2>{{ pg.t }}</h2></div>
                  <div v-if="alumnos.length" class="hv">
                    <div class="stu" role="list">
                      <button v-for="x in alumnos" :key="x.id" :aria-pressed="x.id === ui.alumnoHV" @click="ui.alumnoHV = x.id">
                        {{ nombre(x) }}<span class="c">{{ (hv.by[x.id] || []).length || '' }}</span>
                      </button>
                    </div>
                    <div v-if="alumnoHV">
                      <div class="ficha">
                        <h3>{{ nombre(alumnoHV) }}</h3>
                        <div>RUN<b>{{ formatoRun(alumnoHV.run) || '—' }}</b></div>
                        <div>Edad al 31/03<b>{{ edad31Marzo(alumnoHV.fechaNac) || '—' }}</b></div>
                        <div>NEE<b>{{ alumnoHV.nee || '—' }}</b></div>
                        <div>Diagnóstico<b>{{ alumnoHV.diagnostico || '—' }}</b></div>
                        <div>Apoderado(a)<b>{{ fam(alumnoHV).apoderado || '—' }}{{ fam(alumnoHV).telefono ? ' · ' + fam(alumnoHV).telefono : '' }}</b></div>
                        <div>Asistencia anual<b :style="{ color: asistenciaAnual != null && asistenciaAnual < 85 ? 'var(--bad)' : 'inherit' }">{{ asistenciaAnual == null ? 'Sin registros' : asistenciaAnual + '%' }}</b></div>
                      </div>
                      <div v-if="puede" class="acta" style="margin-bottom:14px">
                        <div class="g3">
                          <label>Fecha<input v-model="nuevaAnot.fecha" type="date"></label>
                          <label>Tipo<select v-model="nuevaAnot.tipo"><option>Observación</option><option>Positiva</option><option>Negativa</option></select></label>
                          <label>Registra<input :value="$auth.user?.name" disabled></label>
                        </div>
                        <label>Anotación<textarea v-model="nuevaAnot.texto" rows="2" placeholder="Describe el hecho observado, avances o apoyos entregados" /></label>
                        <div><button class="btn primary chico" @click="agregarAnotacion">Agregar a la hoja de vida</button></div>
                      </div>
                      <div class="stack">
                        <div v-for="r in anotaciones" :key="r.id" class="entry" :class="r.tipo">
                          <div class="meta">
                            <span class="pill" :class="r.tipo === 'Positiva' ? 'ok' : r.tipo === 'Negativa' ? 'bad' : 'info'">{{ r.tipo }}</span>
                            <span>{{ fecha(r.fecha) }}</span><span>{{ r.autor }}</span><span class="grow" />
                            <button v-if="puede" class="del" @click="borrar('hv', r.id, () => { hv.by[ui.alumnoHV] = hv.by[ui.alumnoHV].filter(x => x.id !== r.id); guardar('hojas') })">{{ seguro === 'hv' + r.id ? '¿Eliminar?' : 'Eliminar' }}</button>
                          </div>
                          <p>{{ r.texto }}</p>
                        </div>
                        <div v-if="!anotaciones.length" class="list"><div class="empty">Sin anotaciones para este alumno(a).</div></div>
                      </div>
                    </div>
                  </div>
                  <div v-else class="list"><div class="empty"><SinAlumnos /></div></div>
                </template>

                <span v-if="pg.id !== 'portada'" class="folio">{{ actual }}</span>
              </section>
            </transition>
          </div>
        </div>
      </div>
      <p class="hint" style="margin-top:12px">Hojea con <kbd>←</kbd> <kbd>→</kbd> o con los separadores de colores.{{ puede ? '' : ' Tienes acceso de solo lectura.' }}</p>
    </div>
  </q-page>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount, getCurrentInstance, h } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { mensajeError } from '../../../composables/useCrud'
import { formatoRun } from '../../../composables/formato'

definePage({ meta: { title: 'Libro de clases PIE', permiso: 'pie.ver' } })

const { proxy } = getCurrentInstance()
const route = useRoute()
const router = useRouter()
const puede = computed(() => proxy.$auth.hasPermission('pie.editar'))

const SinAlumnos = () => h('span', ['No hay estudiantes PIE en este curso. Ingrésalos desde ', h('a', { href: '#', onClick: e => { e.preventDefault(); router.push('/pie') } }, 'PIE / Inclusión'), '.'])

// ── Constantes del libro impreso ──
const MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
const MESES_LECTIVOS = [3, 4, 5, 6, 7, 8, 9, 10, 11, 12]
const DIAS = ['D', 'L', 'M', 'M', 'J', 'V', 'S']
const SEXO = { M: 'H', F: 'M', X: 'X' } // ficha: M masculino / F femenino → libro: H / M
const ESTADO_ASIS = { P: 'presente', A: 'ausente', J: 'justificado', R: 'atraso' }
const CICLO_ASIS = ['', 'P', 'A', 'J', 'R']
const CICLO_LOGRO = ['', 'L', 'ML', 'PL', 'NO']
const PIE_ASIS = [['Asistencia', ['P', 'R']], ['Inasistencia', ['A', 'J']], ['Matrícula', null]]
const PERIODOS = { '1S': '1° Semestre', '2S': '2° Semestre', '1T': '1° Trimestre', '2T': '2° Trimestre', '3T': '3° Trimestre' }
const PERIODOS_PLAN = [['org', 13, 'Período de organización'], ['eje', 14, 'Período de realización o ejecución'], ['fin', 15, 'Período de finalización']]
const TIPOS_ENTREVISTA = ['Entrevista individual', 'Reunión de apoderados', 'Entrega de informe', 'Citación']
const INSTRUMENTOS = ['Lista de cotejo', 'Escala de apreciación', 'Rúbrica', 'Registro anecdótico', 'Prueba adaptada', 'Observación directa', 'Portafolio', 'Autoevaluación']
const CAMPOS_GENERAL = [
  ['educador', 'Nombre del educador(a) diferencial', true],
  ['profesorAula', 'Profesor(a) de aula (co-docencia)', true],
  ['establecimiento', 'Nombre del establecimiento', true],
  ['rbd', 'RBD'], ['localidad', 'Localidad'],
  ['comuna', 'Comuna'], ['provincia', 'Provincia'],
  ['region', 'Región', true],
]
const COLS_FAMILIA = [['apoderado', 'Nombre del apoderado(a)', 180], ['parentesco', 'Parentesco', 100], ['telefono', 'Teléfono', 120], ['email', 'Correo', 170], ['viveCon', 'Vive con', 130], ['ocupacion', 'Ocupación apoderado', 140], ['apSuplente', 'Apoderado suplente', 160]]
const ICONOS = ['Visual', 'Auditiva', 'Cognitiva', 'Motora', 'Del lenguaje', 'Mental', 'TEA'].map((t, i, arr) => {
  const a = Math.PI * (0.06 + 0.88 * i / (arr.length - 1))
  return { t, x: (160 + Math.cos(a) * 116).toFixed(1), y: (158 + Math.sin(a) * 116).toFixed(1) }
})

const PAGES = [
  { id: 'portada', s: 'Portada', t: 'Portada', c: '#1b2a52' },
  { id: 'general', s: 'General', t: 'Información general', c: '#2c58a6' },
  { id: 'contenido', s: 'Índice', t: 'Contenido', c: '#5873a8' },
  { id: 'resumen', s: 'Resumen', t: 'Resumen del curso', c: '#23845a' },
  { id: 'matricula', s: 'Matrícula', t: 'Registro de matrícula', c: '#2c6fb8' },
  { id: 'familia', s: 'Familia', t: 'Antecedentes familiares', c: '#3a86a8' },
  { id: 'asistencia', s: 'Asistencia', t: 'Asistencia diaria', c: '#b8452f' },
  { id: 'actividades', s: 'Actividades', t: 'Registro de actividades', c: '#c27a12' },
  { id: 'evaluaciones', s: 'Evaluaciones', t: 'Evaluaciones', c: '#6b4fb0' },
  { id: 'plan', s: 'Plan anual', t: 'Plan de trabajo anual', c: '#4a8f2c' },
  { id: 'entrevistas', s: 'Entrevistas', t: 'Entrevistas con apoderados', c: '#a8456e' },
  { id: 'hojas', s: 'Hoja de vida', t: 'Hoja de vida', c: '#3d5a80' },
]
const TOC = [
  [1, 'Información general', 'general'], [2, 'Registro de matrícula', 'matricula'], [null, 'Antecedentes generales del (la) alumno(a)', 'matricula'],
  [3, 'Antecedentes familiares', 'familia'], [4, 'Registro de asistencia diaria', 'asistencia'], [5, 'Asistencia diaria', 'asistencia'],
  [6, 'Registro de actividades diarias', 'actividades'], [7, 'Registro de actividades', 'actividades'], [8, 'Registro de evaluaciones', 'evaluaciones'],
  [9, 'Evaluaciones', 'evaluaciones'], [10, 'Elaboración del plan de trabajo anual', 'plan'], [11, 'Objetivos generales', 'plan'],
  [12, 'Objetivos específicos', 'plan'], [13, 'Período de organización', 'plan'], [14, 'Período de realización o ejecución', 'plan'],
  [15, 'Período de finalización', 'plan'], [16, 'Reuniones y/o entrevistas con apoderados', 'entrevistas'], [17, 'Hoja de vida de los(as) alumnos(as)', 'hojas'],
]

// ── Utilidades ──
const pad = n => String(n).padStart(2, '0')
const hoyISO = () => { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}` }
const fecha = iso => { if (!iso) return ''; const [y, m, d] = iso.slice(0, 10).split('-'); return `${d}-${m}-${y}` }
const fechaHora = s => s ? `${fecha(s)} ${s.slice(11, 16)}` : ''
const uid = () => Math.random().toString(36).slice(2, 9) + Date.now().toString(36).slice(-3)
const nombre = s => [s.apPat, s.apMat].filter(Boolean).join(' ') + (s.nombres ? ', ' + s.nombres : '') || 'Sin nombre'
const esFinde = (y, m, d) => { const w = new Date(y, m - 1, d).getDay(); return w === 0 || w === 6 }
const diasDel = (y, m) => new Date(y, m, 0).getDate()
const filaVacia = () => ({ hora: '', materia: '', actividades: '', inasist: '', asist: '', atrasos: '', eval: '' })
const mesPorDefecto = () => { const m = new Date().getMonth() + 1; return m >= 3 ? m : 3 }

function estadisticas (doc, id) {
  const r = (doc?.m || {})[id] || {}
  let p = 0; let a = 0; let j = 0; let rr = 0
  for (const v of Object.values(r)) { if (v === 'P') p++; else if (v === 'A') a++; else if (v === 'J') j++; else if (v === 'R') rr++ }
  const asist = p + rr; const inas = a + j; const tot = asist + inas
  return { asist, inas, tot, pct: tot ? Math.round(asist * 1000 / tot) / 10 : null }
}

function porDefecto (clave) {
  if (clave === 'general') return { ciclo: '', educador: '', profesorAula: '', establecimiento: '', rbd: '', comuna: '', provincia: '', region: '', localidad: '' }
  if (clave === 'plan') return { elaboracion: '', generales: '', especificos: '', org: { ini: '', fin: '', txt: '' }, eje: { ini: '', fin: '', txt: '' }, fin: { ini: '', fin: '', txt: '' } }
  if (clave === 'matricula' || clave === 'familia') return { by: {} }
  if (clave === 'hojas') return { by: {} }
  if (clave === 'entrevistas') return { list: [] }
  if (clave.startsWith('asistencia/')) return { m: {} }
  if (clave.startsWith('actividades/')) return { rows: Array.from({ length: 6 }, filaVacia), obs: '' }
  if (clave.startsWith('evaluaciones/')) return { fecha: '', areas: Array.from({ length: 5 }, () => ({ nombre: '', obj: ['', '', '', '', ''] })), n: {}, final: {} }
  return {}
}

// ── Estado ──
const actualAnio = new Date().getFullYear()
const anios = [actualAnio - 1, actualAnio, actualAnio + 1]
const anio = ref(Number(route.query.anio) || actualAnio)
const cursos = ref([])
const cargandoCursos = ref(false)
const cursoId = ref(Number(route.query.curso) || null)
const libro = ref(null)
const listo = ref(false)
const docs = reactive({})
const firmas = reactive({})
const actual = ref(0)
const direccion = ref(1)
const ui = reactive({ mes: mesPorDefecto(), fecha: hoyISO(), periodo: '1S', alumnoHV: null })
const seguro = ref(null)
const nuevaAnot = reactive({ fecha: hoyISO(), tipo: 'Observación', texto: '' })
const actividadesAnio = ref([])
const actividadesMes = ref([])
const timers = {}
const guardado = reactive({ pendientes: 0, error: false })

const pg = computed(() => PAGES[actual.value])
const alumnos = computed(() => libro.value?.alumnos || [])
const anioLibro = computed(() => libro.value?.curso.anio || anio.value)
const gen = computed(() => docs.general || porDefecto('general'))
const plan = computed(() => docs.plan)
const ent = computed(() => docs.entrevistas)
const hv = computed(() => docs.hojas)

const timersVersion = ref(0) // `timers` no es reactivo: esto fuerza el recálculo de `sync`
const sync = computed(() => {
  if (!puede.value) return { clase: 'local', texto: 'Solo lectura' }
  if (timersVersion.value >= 0 && (Object.keys(timers).length || guardado.pendientes)) return { clase: 'saving', texto: 'Guardando…' }
  if (guardado.error) return { clase: 'err', texto: 'Sin guardar' }
  return { clase: '', texto: 'Guardado' }
})

// ── Carga y guardado de hojas ──
const api = path => `pie/libro/${cursoId.value}${path}`

function aplicar (h) {
  docs[h.clave] = Object.assign(porDefecto(h.clave), h.datos || {})
  firmas[h.clave] = h.firma || null
}

async function cargarDoc (clave) {
  if (docs[clave]) return docs[clave]
  const { data } = await proxy.$axios.get(api(`/hojas/${clave}`))
  aplicar(data)
  return docs[clave]
}

async function cargarSerie (prefijo, desde = '') {
  const { data } = await proxy.$axios.get(api(`/serie/${prefijo}`), { params: { desde } })
  data.forEach(h => { if (!timers[h.clave]) aplicar(h) })
  return data
}

function guardar (clave) {
  if (!puede.value) return
  clearTimeout(timers[clave])
  timers[clave] = setTimeout(() => flush(clave), 650)
  timersVersion.value++
}

async function flush (clave) {
  clearTimeout(timers[clave])
  delete timers[clave]
  guardado.pendientes++
  try {
    await proxy.$axios.put(api(`/hojas/${clave}`), { datos: docs[clave] })
    guardado.error = false
  } catch (err) {
    guardado.error = true
    proxy.$alert.error(mensajeError(err))
  } finally {
    guardado.pendientes--
    timersVersion.value++
  }
}

async function flushTodo () {
  await Promise.all(Object.keys(timers).map(flush))
}

// ── Navegación ──
async function cargarCursos () {
  cargandoCursos.value = true
  try {
    const { data } = await proxy.$axios.get('pie/libro/cursos', { params: { anio: anio.value } })
    cursos.value = data
  } catch (err) {
    proxy.$alert.error(mensajeError(err))
  } finally {
    cargandoCursos.value = false
  }
}

function abrir (id) {
  router.push({ query: { curso: id, anio: anio.value } })
  cursoId.value = id
  cargarLibro()
}

async function cerrar () {
  await flushTodo()
  router.push({ query: { anio: anio.value } })
  cursoId.value = null
  libro.value = null
  cargarCursos()
}

async function cargarLibro () {
  listo.value = false
  Object.keys(docs).forEach(k => delete docs[k])
  try {
    const { data } = await proxy.$axios.get(api(''))
    libro.value = data
    anio.value = data.curso.anio
    ;['general', 'plan', 'matricula', 'familia', 'entrevistas', 'hojas'].forEach(c => aplicar(data.hojas[c] || { clave: c }))
    // Primera vez: la información general parte con lo que ya sabe el sistema (no se guarda hasta editar)
    if (!data.hojas.general) {
      Object.assign(docs.general, {
        educador: data.educadores[0] || '',
        profesorAula: data.curso.profesor_jefe || '',
        establecimiento: data.establecimiento?.nombre || '',
        rbd: data.establecimiento?.rbd || '',
      })
    }
    // Antecedentes familiares: el apoderado de la ficha mientras no se haya escrito otro
    data.alumnos.forEach(s => { fam(s); extra(s.id) })
    if (data.curso.anio !== actualAnio) ui.fecha = `${data.curso.anio}-03-01`
    const inicio = PAGES.findIndex(p => p.id === route.hash?.slice(1))
    actual.value = inicio > 0 ? inicio : 0
    await prepararPagina(pg.value.id)
    listo.value = true
  } catch (err) {
    proxy.$alert.error(mensajeError(err))
    cursoId.value = null
    cargarCursos()
  }
}

async function prepararPagina (id) {
  const y = anioLibro.value
  try {
    if (id === 'resumen') {
      await Promise.all([cargarDoc(`asistencia/${y}-${pad(mesPorDefecto())}`), cargarSerie('actividades', `${y}-`).then(d => { actividadesAnio.value = d.map(h => h.clave) })])
    } else if (id === 'asistencia') {
      await cargarDoc(`asistencia/${y}-${pad(ui.mes)}`)
    } else if (id === 'actividades') {
      const [fy, fm] = ui.fecha.split('-')
      const serie = await cargarSerie('actividades', `${fy}-${fm}`)
      actividadesMes.value = serie.map(h => h.clave.slice(12))
      await Promise.all([cargarDoc(`actividades/${ui.fecha}`), cargarDoc(`asistencia/${fy}-${fm}`)])
    } else if (id === 'evaluaciones') {
      await cargarDoc(`evaluaciones/${ui.periodo}`)
    } else if (id === 'hojas') {
      await cargarSerie('asistencia', `${y}-`)
      if (!ui.alumnoHV || !alumnos.value.some(s => s.id === ui.alumnoHV)) ui.alumnoHV = alumnos.value[0]?.id || null
    }
  } catch (err) {
    proxy.$alert.error(mensajeError(err))
  }
}

async function ir (i) {
  if (i < 0 || i >= PAGES.length || i === actual.value) return
  await prepararPagina(PAGES[i].id)
  direccion.value = i > actual.value ? 1 : -1
  actual.value = i
  seguro.value = null
  try { history.replaceState(history.state, '', '#' + PAGES[i].id) } catch { /* sin hash */ }
}
const irA = id => ir(PAGES.findIndex(p => p.id === id))
function verHoja (id) { ui.alumnoHV = id; irA('hojas') }

function teclas (e) {
  if (!libro.value || e.target.closest?.('input,textarea,select,[contenteditable]') || e.metaKey || e.ctrlKey || e.altKey) return
  if (e.key === 'ArrowRight' || e.key === 'PageDown') { e.preventDefault(); ir(actual.value + 1) }
  if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); ir(actual.value - 1) }
}

// Confirmación en dos toques (como el libro): el primer clic pide confirmar
function borrar (tipo, id, accion) {
  if (seguro.value === tipo + id) { seguro.value = null; accion() } else {
    seguro.value = tipo + id
    setTimeout(() => { if (seguro.value === tipo + id) seguro.value = null }, 3000)
  }
}

const pct = n => alumnos.value.length ? n / alumnos.value.length * 100 : 0
function edad31Marzo (fn) {
  if (!fn) return ''
  const [y, m] = fn.split('-').map(Number)
  if (!y) return ''
  let e = Number(anioLibro.value) - y
  if (m > 3) e--
  return e >= 0 && e < 40 ? e : ''
}

// ── Matrícula y familia ──
function extra (id) { return (docs.matricula.by[id] ??= { comuna: '', obs: '' }) }
function fam (s) { return (docs.familia.by[s.id] ??= { apoderado: s.apoderado || '', telefono: s.telefono || '', email: s.email || '' }) }

// ── Resumen ──
const resumen = computed(() => {
  const mes = mesPorDefecto()
  const doc = docs[`asistencia/${anioLibro.value}-${pad(mes)}`]
  const list = alumnos.value
  let suma = 0; let cnt = 0; const bajos = []
  list.forEach(s => { const st = estadisticas(doc, s.id); if (st.pct != null) { suma += st.pct; cnt++; if (st.pct < 85) bajos.push([s, st]) } })
  const diag = {}
  list.forEach(s => { const k = (s.diagnostico || '').trim() || 'Sin registrar'; diag[k] = (diag[k] || 0) + 1 })
  return {
    mes,
    H: list.filter(s => s.sexo === 'M').length,
    M: list.filter(s => s.sexo === 'F').length,
    perm: list.filter(s => s.nee === 'Permanente').length,
    trans: list.filter(s => s.nee === 'Transitoria').length,
    avg: cnt ? Math.round(suma / cnt * 10) / 10 : null,
    cnt,
    bajos,
    diag: Object.entries(diag).sort((a, b) => b[1] - a[1]),
    clases: actividadesAnio.value.length,
    entrevistas: ent.value?.list.length || 0,
  }
})

// ── Asistencia ──
const asisClave = computed(() => `asistencia/${anioLibro.value}-${pad(ui.mes)}`)
const asis = computed(() => {
  const y = anioLibro.value; const m = ui.mes
  const doc = docs[asisClave.value] || { m: {} }
  const dias = Array.from({ length: diasDel(y, m) }, (_, i) => ({ d: i + 1, we: esFinde(y, m, i + 1), dia: DIAS[new Date(y, m - 1, i + 1).getDay()] }))
  const trabajados = dias.filter(d => alumnos.value.some(s => (doc.m[s.id] || {})[d.d])).map(d => d.d)
  const stats = Object.fromEntries(alumnos.value.map(s => [s.id, estadisticas(doc, s.id)]))
  return { doc, dias, trabajados, stats }
})
const marca = (id, d) => (asis.value.doc.m[id] || {})[d] || ''
const contar = (d, set) => alumnos.value.filter(s => set.includes((asis.value.doc.m[s.id] || {})[d])).length

function ciclar (id, d) {
  const doc = docs[asisClave.value]
  doc.m[id] ??= {}
  const nv = CICLO_ASIS[(CICLO_ASIS.indexOf(doc.m[id][d] || '') + 1) % CICLO_ASIS.length]
  if (nv) doc.m[id][d] = nv; else delete doc.m[id][d]
  guardar(asisClave.value)
}

function todosPresentes (d) {
  if (!puede.value) return
  const doc = docs[asisClave.value]
  const todos = alumnos.value.every(s => (doc.m[s.id] || {})[d] === 'P')
  alumnos.value.forEach(s => {
    doc.m[s.id] ??= {}
    if (todos) delete doc.m[s.id][d]; else if (!doc.m[s.id][d]) doc.m[s.id][d] = 'P'
  })
  guardar(asisClave.value)
}

// ── Actividades ──
const act = computed(() => {
  const clave = `actividades/${ui.fecha}`
  const doc = docs[clave] || porDefecto(clave)
  const [y, m, d] = ui.fecha.split('-').map(Number)
  const asisDia = docs[`asistencia/${y}-${pad(m)}`]?.m || {}
  let H = 0; let M = 0; let hay = false
  alumnos.value.forEach(s => {
    const v = (asisDia[s.id] || {})[d]
    if (v) hay = true
    if (v === 'P' || v === 'R') { if (s.sexo === 'M') H++; else if (s.sexo === 'F') M++ }
  })
  const dias = [...new Set([...actividadesMes.value, ui.fecha])].filter(x => x.slice(0, 7) === ui.fecha.slice(0, 7)).sort()
  const dt = new Date(y, m - 1, d)
  const firma = firmas[clave]
  return {
    clave, doc, H, M, hay, dias, firma,
    bloqueado: !puede.value || !!firma,
    titulo: `${['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][dt.getDay()]} ${d} de ${MESES[m - 1].toLowerCase()}`,
  }
})

async function firmar () {
  const clave = act.value.clave
  try {
    if (timers[clave]) await flush(clave)
    const { data } = await proxy.$axios.post(api(`/actividades/${ui.fecha}/firma`))
    firmas[clave] = data.firma
    if (!actividadesMes.value.includes(ui.fecha)) actividadesMes.value.push(ui.fecha)
    proxy.$alert.success('Registro del día firmado.')
  } catch (err) {
    proxy.$alert.error(mensajeError(err))
  }
}

async function quitarFirma () {
  try {
    const { data } = await proxy.$axios.delete(api(`/actividades/${ui.fecha}/firma`))
    firmas[act.value.clave] = data.firma
  } catch (err) {
    proxy.$alert.error(mensajeError(err))
  }
}

// ── Evaluaciones ──
const evClave = computed(() => `evaluaciones/${ui.periodo}`)
const ev = computed(() => docs[evClave.value] || porDefecto(evClave.value))
const logro = (id, a, o) => (ev.value.n[id] || {})[`${a}-${o}`] || ''
function ciclarLogro (id, a, o) {
  const doc = ev.value
  doc.n[id] ??= {}
  const k = `${a}-${o}`
  const nv = CICLO_LOGRO[(CICLO_LOGRO.indexOf(doc.n[id][k] || '') + 1) % CICLO_LOGRO.length]
  if (nv) doc.n[id][k] = nv; else delete doc.n[id][k]
  guardar(evClave.value)
}
function sugerencia (id) {
  const n = ev.value.n[id] || {}
  let s = 0; let c = 0
  for (const v of Object.values(n)) { if (v === 'L') { s += 3; c++ } else if (v === 'ML') { s += 2; c++ } else if (v === 'PL') { s += 1; c++ } }
  if (!c) return ''
  const p = s / c
  return p >= 2.5 ? 'Logrado' : p >= 1.75 ? 'Medianamente logrado' : 'Por lograr'
}

// ── Entrevistas ──
const entrevistasOrdenadas = computed(() => [...(ent.value?.list || [])].sort((a, b) => (b.fecha || '').localeCompare(a.fecha || '')))
function nuevaEntrevista () {
  ent.value.list.push({ id: uid(), fecha: hoyISO(), alumnoId: null, tipo: 'Entrevista individual', asistente: '', parentesco: '', profesional: gen.value.educador || proxy.$auth.user?.name || '', motivo: '', acuerdos: '' })
  guardar('entrevistas')
}

// ── Hoja de vida ──
const alumnoHV = computed(() => alumnos.value.find(s => s.id === ui.alumnoHV))
const anotaciones = computed(() => [...(hv.value?.by[ui.alumnoHV] || [])].sort((a, b) => (b.fecha || '').localeCompare(a.fecha || '')))
const asistenciaAnual = computed(() => {
  if (!alumnoHV.value) return null
  let a = 0; let t = 0
  MESES_LECTIVOS.forEach(m => { const st = estadisticas(docs[`asistencia/${anioLibro.value}-${pad(m)}`], alumnoHV.value.id); a += st.asist; t += st.tot })
  return t ? Math.round(a * 1000 / t) / 10 : null
})
function agregarAnotacion () {
  const texto = nuevaAnot.texto.trim()
  if (!texto) { proxy.$alert.warning('Escribe la anotación antes de agregarla.'); return }
  ;(hv.value.by[ui.alumnoHV] ??= []).push({ id: uid(), fecha: nuevaAnot.fecha, tipo: nuevaAnot.tipo, autor: proxy.$auth.user?.name || '', texto })
  nuevaAnot.texto = ''
  guardar('hojas')
  proxy.$alert.success('Anotación agregada.')
}

// Atrás/adelante del navegador entre la lista de cursos y un libro
watch(() => route.query.curso, async q => {
  const id = Number(q) || null
  if (id === cursoId.value) return
  await flushTodo()
  cursoId.value = id
  if (id) cargarLibro(); else { libro.value = null; cargarCursos() }
})

function antesDeSalir () { Object.keys(timers).forEach(flush) }

onMounted(() => {
  window.addEventListener('keydown', teclas)
  window.addEventListener('beforeunload', antesDeSalir)
  if (cursoId.value) cargarLibro(); else cargarCursos()
})
onBeforeUnmount(() => {
  window.removeEventListener('keydown', teclas)
  window.removeEventListener('beforeunload', antesDeSalir)
  antesDeSalir()
})
</script>

<style scoped>
/* Formato del libro impreso (ui/Libro de Clases PIE.html) */
.libroPie {
  --desk:#dde3e8; --desk-2:#cfd7de;
  --paper:#fbfcfa; --paper-2:#f2f5f8; --edge:#d9e0e8;
  --ink:#1c2b4f; --muted:#5a6884; --hand:#183f8f;
  --rule:#a8bce0; --rule-soft:#d3dff0; --band:#dfe9f6;
  --brand:#2c58a6; --navy:#1b2a52; --lime:#8cc63f; --sky:#3ea6e6; --grass:#4caf3d;
  --ok:#23845a; --warn:#b97a10; --bad:#bf3a33; --info:#2c6fb8; --none:#7a8296;
  --weekend:#eef1f5; --focus:#f2a900;
  --display:"Baloo 2","Trebuchet MS",system-ui,sans-serif;
  --body:"Atkinson Hyperlegible",system-ui,-apple-system,"Segoe UI",sans-serif;
  --shadow:0 1px 0 rgba(20,30,60,.06),0 18px 40px -18px rgba(20,30,60,.35);
  padding: 16px;
}
.libroPie button, .libroPie input, .libroPie select, .libroPie textarea { font: inherit; color: inherit }
.libroPie :focus-visible { outline: 3px solid var(--focus); outline-offset: 2px; border-radius: 4px }

/* selección de curso */
.cursosGrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 12px }
.cursoCard { display: grid; gap: 3px; text-align: left; border: 1px solid #d9e3ec; border-left: 5px solid var(--brand); border-radius: 14px; padding: 14px 16px; background: #fff; cursor: pointer; transition: transform .12s, box-shadow .12s }
.cursoCard:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -10px rgba(11,36,64,.4) }
.cursoCard b { font-size: 17px; color: #0b2440 }
.cursoCard small { color: var(--muted) }
.cursoCard .jefe { font-size: 11.5px }
.cursoCard .cursoIcon { color: var(--brand); font-size: 18px; margin-bottom: 4px }
.cursoCard.vacio { border-left-color: #c5ccd6; opacity: .75 }

.wrap { max-width: 1240px; margin: 0 auto; font-family: var(--body); font-size: 15px; line-height: 1.45; color: var(--ink); background: var(--desk); background-image: radial-gradient(circle at 20% 0%, var(--desk-2), transparent 60%); padding: 14px 16px 24px; border-radius: 18px }

/* barra superior */
.bar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px }
.bar h1 { font-family: var(--display); font-weight: 800; font-size: 20px; margin: 0; color: var(--ink); letter-spacing: .2px; line-height: 1 }
.bar h1 small { display: block; font-family: var(--body); font-weight: 400; font-size: 12px; color: var(--muted); letter-spacing: .3px; margin-top: 3px }
.grow { flex: 1 }
.chip { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; padding: 4px 10px; border-radius: 999px; background: var(--paper); border: 1px solid var(--edge); color: var(--muted); white-space: nowrap }
.chip i { width: 8px; height: 8px; border-radius: 50%; background: var(--ok) }
.chip.saving i { background: var(--warn) } .chip.err i { background: var(--bad) } .chip.local i { background: var(--none) }
.nav { display: flex; align-items: center; gap: 6px }
.btn { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--edge); background: var(--paper); color: var(--ink); padding: 7px 12px; border-radius: 8px; cursor: pointer; font-size: 14px; line-height: 1.1 }
.btn:hover { border-color: var(--rule) }
.btn.primary { background: var(--brand); border-color: var(--brand); color: var(--paper) }
.btn.ghost { background: transparent }
.btn:disabled { opacity: .45; cursor: default }
.btn.chico { padding: 4px 9px; font-size: 13px }
.pg { font-variant-numeric: tabular-nums; font-size: 13px; color: var(--muted); min-width: 64px; text-align: center }

/* libro */
.desk { display: flex; flex-direction: column }
.book { position: relative; z-index: 1; min-width: 0; border-radius: 6px 14px 14px 6px; box-shadow: var(--shadow); background: var(--paper) }
.book::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 22px; border-radius: 6px 0 0 6px; background: linear-gradient(90deg, var(--navy), color-mix(in srgb, var(--navy) 70%, var(--paper)) 70%, var(--edge)); z-index: 3; pointer-events: none }
.book::after { content: ""; position: absolute; top: 10px; bottom: 10px; right: -5px; width: 5px; border-radius: 0 4px 4px 0; background: repeating-linear-gradient(180deg, var(--paper-2) 0 2px, var(--edge) 2px 3px); z-index: -1 }
.sheet { display: grid; perspective: 2400px; min-height: 620px }
.hoja { grid-area: 1/1; background: var(--paper); border-radius: 6px 14px 14px 6px; padding: 22px 26px 26px 46px; min-width: 0; position: relative; transform-origin: 0 50%; backface-visibility: hidden }
.pasa-leave-active { animation: turnOut .45s cubic-bezier(.45,.05,.4,1) forwards; box-shadow: -30px 0 60px -30px rgba(0,0,0,.35); z-index: 2 }
.pasa-enter-active, .vuelve-leave-active { animation: fade .2s ease both }
.vuelve-enter-active { animation: turnIn .45s cubic-bezier(.3,.6,.4,1) both; z-index: 2 }
@keyframes turnOut { 0% { transform: rotateY(0) } 70% { opacity: 1 } 100% { transform: rotateY(-115deg); opacity: 0 } }
@keyframes turnIn { 0% { transform: rotateY(-115deg); opacity: 0 } 30% { opacity: 1 } 100% { transform: rotateY(0) } }
@keyframes fade { from { opacity: 0 } to { opacity: 1 } }
@media (prefers-reduced-motion: reduce) { .pasa-leave-active, .vuelve-enter-active { animation: fade .01s both } }
.folio { position: absolute; bottom: 10px; right: 22px; font-size: 12px; color: var(--muted); font-variant-numeric: tabular-nums }

/* separadores */
.ribbons { display: flex; gap: 3px; padding: 0 14px 0 30px; overflow-x: auto; scrollbar-width: thin; margin-bottom: -2px; position: relative; z-index: 0 }
.ribbon { flex: 1 0 auto; border: 0; cursor: pointer; padding: 7px 10px 9px; border-radius: 9px 9px 0 0; font-size: 12px; font-weight: 700; letter-spacing: .3px; color: #fff !important; background: var(--c); opacity: .72; transform: translateY(5px); transition: transform .15s, opacity .15s; white-space: nowrap }
.ribbon:hover { opacity: .95; transform: translateY(2px) }
.ribbon[aria-current="true"] { opacity: 1; transform: translateY(0) }
@media (max-width: 860px) {
  .ribbons { padding: 0 4px 0 18px }
  .ribbon { flex: none }
  .hoja { padding: 16px 14px 30px 30px }
  .book::before { width: 14px }
}

/* banda de encabezado */
.band { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; background: var(--band); border: 1px solid var(--rule); border-radius: 4px 22px 4px 4px; padding: 8px 14px; margin-bottom: 16px }
.band h2 { margin: 0; font-family: var(--body); font-weight: 700; font-size: 18px; line-height: 1.3; letter-spacing: 2.5px; text-transform: uppercase; color: var(--brand) }
.band .n { font-family: var(--display); font-weight: 800; color: var(--paper); background: var(--brand); border-radius: 6px; min-width: 28px; height: 28px; display: grid; place-items: center; font-size: 15px; padding: 0 6px }
.band label.inl { display: flex; align-items: center; gap: 6px; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: var(--brand); font-weight: 700 }
.band select, .band input { border: 1px solid var(--rule); background: var(--paper); border-radius: 6px; padding: 4px 8px; font-size: 14px; text-transform: none; letter-spacing: 0; font-weight: 400; color: var(--hand) }
.lead { color: var(--muted); margin: -6px 0 14px; max-width: 70ch; font-size: 14px }
.tag { display: inline-block; font-size: 10.5px; letter-spacing: .8px; text-transform: uppercase; font-weight: 700; padding: 2px 7px; border-radius: 4px; background: color-mix(in srgb, var(--lime) 30%, transparent); color: var(--ink) }

/* formularios rayados */
.form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 4px 28px; border: 1px solid var(--rule); border-radius: 4px 22px 4px 4px; padding: 18px 22px }
.form .full { grid-column: 1/-1 }
.field { display: flex; align-items: flex-end; gap: 10px; border-bottom: 1px solid var(--rule); padding: 12px 0 2px; min-width: 0 }
.field > span { font-size: 12px; text-transform: uppercase; letter-spacing: 1.2px; color: var(--brand); white-space: nowrap; font-weight: 700; padding-bottom: 4px }
.field input { flex: 1; min-width: 0; border: 0; background: transparent; padding: 2px 2px 4px; color: var(--hand); font-size: 16px }
.field input:focus { outline: none; background: color-mix(in srgb, var(--band) 70%, transparent) }
@media (max-width: 640px) { .form { grid-template-columns: 1fr; padding: 12px 14px } }

/* tablas */
.scroll { overflow-x: auto; border: 1px solid var(--rule); border-radius: 4px 18px 4px 4px; background: var(--paper) }
table.grid { border-collapse: collapse; width: 100%; font-size: 13.5px; font-variant-numeric: tabular-nums }
.grid th { background: var(--band); color: var(--brand); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .6px; padding: 6px; border: 1px solid var(--rule); text-align: center; vertical-align: bottom; line-height: 1.15 }
.grid td { border: 1px solid var(--rule-soft); border-left-color: var(--rule); border-right-color: var(--rule); padding: 0; height: 32px; vertical-align: middle }
.grid td.num { text-align: center; color: var(--muted); width: 34px; font-size: 12px }
.grid td.ro { padding: 4px 8px; white-space: nowrap }
.grid td.center { text-align: center }
.grid td input, .grid td select, .grid td textarea { width: 100%; border: 0; background: transparent; padding: 6px 7px; color: var(--hand); font-size: 13.5px; min-width: 0 }
.grid td textarea { resize: vertical; min-height: 32px; display: block; line-height: 1.35 }
.grid td input:focus, .grid td textarea:focus { outline: 2px solid var(--focus); outline-offset: -2px; background: color-mix(in srgb, var(--band) 60%, transparent) }
.grid tr:hover td { background: color-mix(in srgb, var(--band) 35%, transparent) }
.grid tfoot td { background: var(--paper-2); font-weight: 700; text-align: center; font-size: 12px; color: var(--ink) }
.grid tfoot td.lbl { text-align: right; padding: 4px 8px; text-transform: uppercase; letter-spacing: .8px; font-size: 11px; color: var(--brand) }
.del { border: 0; background: transparent; color: var(--muted); cursor: pointer; padding: 4px 8px; border-radius: 6px; text-decoration: none; display: inline-block }
.del:hover { color: var(--bad); background: color-mix(in srgb, var(--bad) 10%, transparent) }
.toolrow { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin: 12px 0 0 }
input:disabled, textarea:disabled, select:disabled { cursor: default; opacity: 1 }

/* asistencia */
.att th.day { min-width: 27px; padding: 4px 2px; font-size: 11px; cursor: pointer }
.att th.day:hover { background: color-mix(in srgb, var(--brand) 18%, var(--band)) }
.att th.day.we { background: var(--weekend); color: var(--muted); cursor: default }
.att th.day small { display: block; font-weight: 400; font-size: 9px; letter-spacing: 0; opacity: .8 }
.att td.c { padding: 0; width: 27px }
.att td.we, .grid tfoot td.we { background: var(--weekend) }
.mk { width: 100%; height: 31px; border: 0; background: transparent; cursor: pointer; font-weight: 700; font-size: 12.5px; padding: 0 }
.mk:disabled { cursor: default }
.mk[data-v="P"] { color: var(--ok) }
.mk[data-v="A"] { color: var(--paper); background: var(--bad) }
.mk[data-v="J"] { color: var(--paper); background: var(--warn) }
.mk[data-v="R"] { color: var(--info); background: color-mix(in srgb, var(--info) 15%, transparent) }
.att td.name { white-space: nowrap; padding: 4px 8px; min-width: 200px; position: sticky; left: 0; background: var(--paper); z-index: 1 }
.att th.name { position: sticky; left: 0; z-index: 2 }
.pct { font-weight: 700 }
.pct.low { color: var(--bad) }
.legend { display: flex; gap: 14px; flex-wrap: wrap; font-size: 12.5px; color: var(--muted); margin-top: 10px; align-items: center }
.legend b { display: inline-grid; place-items: center; width: 22px; height: 20px; border-radius: 4px; margin-right: 4px; font-size: 11.5px }

/* evaluaciones */
.ev { border: 0; width: 100%; height: 31px; cursor: pointer; font-size: 11px; font-weight: 700; background: transparent; padding: 0 }
.ev:disabled { cursor: default }
.ev[data-v="L"] { background: color-mix(in srgb, var(--ok) 22%, transparent); color: var(--ok) }
.ev[data-v="ML"] { background: color-mix(in srgb, var(--info) 20%, transparent); color: var(--info) }
.ev[data-v="PL"] { background: color-mix(in srgb, var(--warn) 22%, transparent); color: var(--warn) }
.ev[data-v="NO"] { background: color-mix(in srgb, var(--none) 18%, transparent); color: var(--none) }
.att th.areah input { background: var(--paper); border: 1px solid var(--rule); border-radius: 4px; padding: 3px 6px; color: var(--hand); font-size: 12px; text-transform: none; letter-spacing: 0; font-weight: 400; width: 100% }
.objs { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-top: 14px }
.objs fieldset { border: 1px solid var(--rule); border-radius: 4px 14px 4px 4px; padding: 8px 12px 10px; margin: 0; min-width: 0 }
.objs legend { font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: var(--brand); font-weight: 700; padding: 0 4px }
.objs label { display: flex; gap: 6px; align-items: baseline; border-bottom: 1px solid var(--rule-soft); padding: 4px 0 }
.objs label b { color: var(--muted); font-size: 12px }
.objs input { border: 0; background: transparent; flex: 1; min-width: 0; color: var(--hand); font-size: 13.5px; padding: 2px }
.sug { font-size: 11px; color: var(--muted); display: block; padding: 0 7px 3px }

/* escritura rayada */
.lined { width: 100%; border: 1px solid var(--rule); border-radius: 4px 14px 4px 4px; padding: 6px 12px; line-height: 30px; font-size: 15px; color: var(--hand); resize: vertical; min-height: 152px; background-color: var(--paper); background-image: repeating-linear-gradient(180deg, transparent 0 29px, var(--rule-soft) 29px 30px); background-attachment: local; background-position: 0 5px }
.lined:focus { outline: 2px solid var(--focus); outline-offset: 1px }
.sec { margin-bottom: 22px }
.sec h3 { font-size: 13px; line-height: 1.4; letter-spacing: 1.6px; text-transform: uppercase; color: var(--brand); margin: 0 0 8px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; font-weight: 700 }
.sec h3 .n { color: var(--muted); font-weight: 400 }
.dates { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 8px; font-size: 13px; color: var(--muted) }
.dates input { border: 0; border-bottom: 1px solid var(--rule); background: transparent; color: var(--hand); padding: 2px 4px }
.periods { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px }
@media (max-width: 900px) { .periods { grid-template-columns: 1fr } }

/* índice */
.toc { list-style: none; margin: 0; padding: 0; border: 1px solid var(--rule); border-radius: 4px 22px 4px 4px; max-width: 820px }
.toc li { border-bottom: 1px solid var(--rule-soft) }
.toc li:last-child { border-bottom: 0 }
.toc button { display: flex; width: 100%; gap: 12px; align-items: baseline; border: 0; background: transparent; text-align: left; padding: 9px 16px; cursor: pointer; font-size: 14px; letter-spacing: .8px; text-transform: uppercase; color: var(--ink) }
.toc button:hover { background: var(--band) }
.toc .no { color: var(--brand); font-weight: 700; min-width: 28px; font-variant-numeric: tabular-nums }
.toc .dots { flex: 1; border-bottom: 1px dotted var(--rule); transform: translateY(-4px) }
.toc .go { font-size: 12px; color: var(--muted); text-transform: none; letter-spacing: 0 }

/* resumen */
.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-bottom: 18px }
.stat { border: 1px solid var(--rule); border-radius: 4px 14px 4px 4px; padding: 12px 14px; background: var(--paper) }
.stat .k { font-size: 11px; text-transform: uppercase; letter-spacing: 1.2px; color: var(--brand); font-weight: 700 }
.stat .v { font-family: var(--display); font-weight: 800; font-size: 34px; line-height: 1.1; font-variant-numeric: tabular-nums }
.stat .s { font-size: 12.5px; color: var(--muted) }
.bars { display: flex; height: 8px; border-radius: 4px; overflow: hidden; background: var(--rule-soft); margin-top: 8px }
.bars span { display: block; height: 100% }
.cols2 { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 18px }
@media (max-width: 800px) { .cols2 { grid-template-columns: 1fr } }
.list { border: 1px solid var(--rule); border-radius: 4px 14px 4px 4px }
.list .fila { display: flex; gap: 10px; align-items: center; padding: 8px 12px; border-bottom: 1px solid var(--rule-soft); font-size: 14px }
.list .fila:last-child { border-bottom: 0 }
.list .fila .grow { min-width: 0 }
.empty { padding: 22px; text-align: center; color: var(--muted); font-size: 14px }
.pill { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; white-space: nowrap }
.pill.bad { background: color-mix(in srgb, var(--bad) 15%, transparent); color: var(--bad) }
.pill.ok { background: color-mix(in srgb, var(--ok) 15%, transparent); color: var(--ok) }
.pill.info { background: color-mix(in srgb, var(--info) 15%, transparent); color: var(--info) }
.pill.mute { background: color-mix(in srgb, var(--none) 15%, transparent); color: var(--none) }

/* actividades */
.actfoot { display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(220px, 300px); gap: 16px; margin-top: 12px; align-items: stretch }
@media (max-width: 900px) { .actfoot { grid-template-columns: 1fr } }
.hmt { border: 1px solid var(--rule); border-radius: 6px; padding: 6px 10px; text-align: center }
.hmt .t { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--brand); font-weight: 700 }
.hmt .boxes { display: flex; gap: 6px; margin-top: 4px; justify-content: center }
.hmt .boxes div { border: 1px solid var(--rule); min-width: 46px; border-radius: 4px; font-size: 11px; color: var(--muted); padding: 2px 4px }
.hmt .boxes b { display: block; font-size: 18px; color: var(--hand); font-variant-numeric: tabular-nums }
.hmt .note { font-size: 11px; color: var(--muted); margin-top: 4px }
.sign { border: 1px solid var(--rule); border-radius: 6px; padding: 10px; display: flex; flex-direction: column; gap: 6px; justify-content: space-between }
.sign .line { border-top: 1px solid var(--ink); text-align: center; font-size: 12px; color: var(--muted); padding-top: 3px; margin-top: auto }
.stamp { border: 2px solid var(--ok); color: var(--ok); border-radius: 6px; padding: 6px 8px; font-size: 12.5px; transform: rotate(-1.5deg); text-align: center }
.stamp b { display: block; font-size: 14px }
.datechips { display: flex; gap: 6px; flex-wrap: wrap; margin: -4px 0 12px }
.datechips button { border: 1px solid var(--rule); background: var(--paper); border-radius: 999px; font-size: 12px; padding: 2px 9px; cursor: pointer; color: var(--hand); font-variant-numeric: tabular-nums }
.datechips button[aria-pressed="true"] { background: var(--brand); color: var(--paper); border-color: var(--brand) }
.obsline { border-bottom: 1px solid var(--rule); color: var(--hand); padding: 4px 2px; font-size: 14px; min-height: 28px }

/* entrevistas / hoja de vida */
.acta { border: 1px solid var(--rule); border-radius: 4px 14px 4px 4px; padding: 12px 14px; display: grid; gap: 8px }
.acta .top { display: flex; gap: 8px; align-items: center; flex-wrap: wrap }
.acta label { display: grid; gap: 2px; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; color: var(--brand); font-weight: 700; min-width: 0 }
.acta input, .acta select, .acta textarea { border: 0; border-bottom: 1px solid var(--rule); background: transparent; color: var(--hand); font-size: 14px; padding: 3px 2px; text-transform: none; letter-spacing: 0; font-weight: 400; min-width: 0; width: 100% }
.acta textarea { resize: vertical; line-height: 1.4 }
.acta .g3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px }
@media (max-width: 640px) { .acta .g3 { grid-template-columns: 1fr } }
.stack { display: grid; gap: 12px }
.hv { display: grid; grid-template-columns: 240px minmax(0, 1fr); gap: 18px }
@media (max-width: 760px) { .hv { grid-template-columns: 1fr } }
.stu { display: flex; flex-direction: column; border: 1px solid var(--rule); border-radius: 4px 14px 4px 4px; max-height: 560px; overflow: auto }
.stu button { border: 0; border-bottom: 1px solid var(--rule-soft); background: transparent; text-align: left; padding: 8px 12px; cursor: pointer; font-size: 14px; display: flex; gap: 8px; align-items: center }
.stu button:hover { background: var(--band) }
.stu button[aria-pressed="true"] { background: var(--brand); color: var(--paper) }
.stu button .c { margin-left: auto; font-size: 11px; opacity: .8; font-variant-numeric: tabular-nums }
.ficha { border: 1px solid var(--rule); border-radius: 4px 22px 4px 4px; padding: 14px 18px; margin-bottom: 14px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px 18px }
.ficha h3 { grid-column: 1/-1; margin: 0; font-family: var(--display); font-size: 22px; font-weight: 800; color: var(--ink); line-height: 1.1 }
.ficha div { font-size: 13px; color: var(--muted) }
.ficha div b { display: block; color: var(--hand); font-size: 14.5px; font-weight: 400 }
.entry { border-left: 4px solid var(--info); padding: 6px 12px; background: var(--paper-2); border-radius: 0 8px 8px 0; display: grid; gap: 4px }
.entry.Positiva { border-color: var(--ok) } .entry.Negativa { border-color: var(--bad) }
.entry .meta { display: flex; gap: 8px; align-items: center; font-size: 12px; color: var(--muted); flex-wrap: wrap }
.entry p { margin: 0; white-space: pre-wrap }

/* portada: ilustración fija */
.hoja.cover { padding: 0; overflow: hidden; min-height: 620px; background: linear-gradient(180deg, #46b2ee 0%, #7ccbf3 55%, #a8dcf5 100%); color: #10224a }
.cover .cloud { position: absolute; background: #fff; border-radius: 999px; opacity: .95; box-shadow: 40px 10px 0 6px #fff, -34px 14px 0 2px #fff }
.cover .hill { position: absolute; left: -10%; right: -10%; bottom: -38%; height: 70%; border-radius: 50% 50% 0 0; background: radial-gradient(ellipse at 40% 20%, #7fd04f, #3f9e33 70%) }
.cover .hill2 { position: absolute; left: -30%; width: 80%; bottom: -30%; height: 58%; border-radius: 50% 50% 0 0; background: #58b63f }
.cover .grassdots { position: absolute; inset: auto 0 0 0; height: 40%; background-image: radial-gradient(#fff9 1.6px, transparent 2px), radial-gradient(#ffe46b 1.6px, transparent 2px); background-size: 46px 38px, 58px 44px; background-position: 0 0, 20px 14px; opacity: .7 }
.insignia { position: absolute; left: 6%; top: 9%; width: min(330px, 46%); aspect-ratio: 1; max-width: 100% }
.insignia svg { width: 100%; height: 100%; display: block; font-family: var(--body) }
.label { position: absolute; right: 5%; bottom: 0; width: min(380px, 58%); background: #1b2a52; color: #fff; border-radius: 190px 190px 0 0; padding: 44px 30px 30px; display: flex; flex-direction: column; align-items: center; gap: 8px; box-shadow: 0 -6px 0 6px #ffffff33 }
.label .t { font-family: var(--display); font-weight: 800; font-size: clamp(34px, 5vw, 52px); line-height: .85; color: #9be04d; text-align: center; -webkit-text-stroke: 1.5px #0b1734; paint-order: stroke fill; text-shadow: 0 3px 0 #0b1734 }
.label .sub { font-size: 13px; text-align: center; letter-spacing: .5px; color: #d6e6ff; line-height: 1.3 }
.label .sub b { display: block; font-size: 15px; color: #fff; letter-spacing: 1px }
.label .slots { display: grid; gap: 7px; width: 100%; margin-top: 8px }
.label .slot { display: flex; align-items: center; gap: 8px; font-size: 10.5px; letter-spacing: 1.2px; text-transform: uppercase; color: #b9c9ea }
.label .slot span { width: 92px; flex: none }
.label .slot b { flex: 1; background: #fff; color: #183f8f; border-radius: 3px; padding: 4px 8px; font-size: 14px; letter-spacing: 0; text-transform: none; font-weight: 400; min-height: 26px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap }
.label .open { margin-top: 10px; background: #9be04d; color: #0b1734 !important; border: 0; border-radius: 999px; padding: 9px 18px; font-weight: 700; cursor: pointer; font-size: 15px }
.label .open:hover { background: #b5ef6b }
.cover .code { position: absolute; left: 5%; bottom: 22px; color: #fff; font-size: 12px; letter-spacing: 2px; text-transform: uppercase; text-shadow: 0 1px 2px #0005 }
@media (max-width: 700px) {
  .hoja.cover { min-height: 760px }
  .insignia { left: 50%; transform: translateX(-50%); top: 5%; width: min(280px, 80%) }
  .label { left: 16px; right: 16px; width: auto; border-radius: 140px 140px 0 0; padding: 36px 18px 22px }
}

.loading { display: grid; place-items: center; min-height: 400px; color: var(--muted) }
.hint { font-size: 12.5px; color: var(--muted) }
kbd { font-family: var(--body); font-size: 11px; border: 1px solid var(--edge); border-bottom-width: 2px; border-radius: 4px; padding: 0 5px; background: var(--paper) }
</style>
