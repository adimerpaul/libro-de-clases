import{t as e}from"./printd-CI3YqDZj.js";var t=e(),n=`
  @page { size: letter portrait; margin: 0; }
  * { box-sizing: border-box; }
  body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #171717; }
  .pagina {
    width: 216mm;
    height: 279mm;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .rotulo {
    width: 118mm;
    min-height: 73mm;
    padding: 7mm 10mm 8mm;
    border: 0.65mm solid #222;
    border-radius: 9mm;
  }
  .logo { display: block; width: 70mm; height: auto; margin: 0 auto 1mm; }
  .titulo {
    margin: 0 0 3mm;
    color: #2d82b7;
    font-size: 13pt;
    font-weight: 700;
    text-align: center;
    text-transform: uppercase;
  }
  table { width: 100%; border-collapse: collapse; font-size: 10.5pt; line-height: 1.35; }
  th { width: 39mm; padding: 0.45mm 2mm 0.45mm 0; color: #2d82b7; text-align: right; vertical-align: top; }
  td { padding: 0.45mm 0; font-weight: 600; text-transform: uppercase; }
`;function r(e){return String(e??``).replace(/&/g,`&amp;`).replace(/</g,`&lt;`).replace(/>/g,`&gt;`).replace(/"/g,`&quot;`).replace(/'/g,`&#039;`)}function i(){return new Intl.DateTimeFormat(`es-BO`,{day:`2-digit`,month:`2-digit`,year:`numeric`,timeZone:`America/La_Paz`}).format(new Date)}function a(e,a=``){let o=`
    <main class="pagina">
      <section class="rotulo">
        <img class="logo" src="${r(`${String(a).replace(/\/$/,``)}/images/logo-laboratorio-urme.jpg`)}" alt="Laboratorio de Diagnóstico Clínico URME">
        <div class="titulo">Laboratorio de Diagnóstico Clínico URME</div>
        <table>
          <tr><th>Nombre:</th><td>${r(e.paciente?.nombre_completo||`NO ASIGNADO`)}</td></tr>
          <tr><th>Médico:</th><td>${r(e.doctor?.nombre||`NO ASIGNADO`)}</td></tr>
          <tr><th>Institución:</th><td>${r(e.paciente?.seguro?.nombre||`PARTICULAR`)}</td></tr>
          <tr><th>Fecha de Transcripción:</th><td>${r(i())}</td></tr>
        </table>
      </section>
    </main>`,s=document.createElement(`div`);s.innerHTML=o,new t.Printd().print(s,[n],[],({launchPrint:e})=>e())}async function o(e,n){let{data:r}=await e.get(`solicitudes-laboratorio/${n}/impresion`,{responseType:`text`}),i=new DOMParser().parseFromString(r,`text/html`),a=Array.from(i.querySelectorAll(`style`),e=>e.textContent),o=document.createElement(`div`);return o.innerHTML=i.body.innerHTML,new Promise((e,n)=>{let r=new t.Printd;r.onAfterPrint(()=>r.getIFrame().remove()),r.print(o,a,[],({iframe:t,launchPrint:r})=>{try{t.contentWindow.focus(),r(),e()}catch(e){t.remove(),n(e)}})})}export{a as n,o as t};