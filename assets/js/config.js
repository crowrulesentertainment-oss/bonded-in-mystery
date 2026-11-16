/* Public browser configuration only. Never place service_role or secret keys here. */
window.BIM_CONFIG = Object.freeze({
  supabaseUrl: "https://cevylpnoexugwgygvtgu.supabase.co",
  supabasePublishableKey: "sb_publishable_AdfM5y6RqvF3tbvEVzDZSg_JuGTQLD-"
});
if (window.supabase && window.BIM_CONFIG && !window.bimSupabase) {
  window.bimSupabase = window.supabase.createClient(window.BIM_CONFIG.supabaseUrl, window.BIM_CONFIG.supabasePublishableKey);
}